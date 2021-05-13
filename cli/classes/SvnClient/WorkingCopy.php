<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\SvnClient;

use RuntimeException;

/**
 * Class WorkingCopy
 *
 * @package Ox\Cli\SvnClient
 */
class WorkingCopy {
  /** @var string */
  private $path;

  /** @var string */
  private $url;

  /**
   * Get path
   *
   * @return string
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Get revision
   *
   * @return int
   */
  public function getRevision() {
    return $this->revision;
  }

  /**
   * Get URL
   *
   * @return string
   */
  public function getURL() {
    return $this->url;
  }

  /** @var int */
  private $revision;

  /** @var Repository */
  protected $repository;

  /**
   * Get SVN repository
   *
   * @return Repository
   */
  public function getRepository() {
    return $this->repository;
  }

  /**
   * WorkingCopy constructor.
   *
   * @param string $path Path
   *
   * @throws Exception
   */
  function __construct($path) {
    if (!is_dir($path)) {
      throw new Exception("'$path' does not exist");
    }

    $this->path = $path;

    $xml = $this->info();

    $data           = Util::parseXML($xml);
    $this->url      = (string)$data->url;
    $this->revision = (int)$data->attributes()->revision;

    $repo             = $data->repository;
    $this->repository = new Repository((string)$repo->root, (string)$repo->uuid);
  }

  /**
   * @return array
   */
  function getBranches() {
    return $this->repository->getBranches();
  }

  /**
   * @return array
   */
  function getTags() {
    return $this->repository->getTags();
  }

  /**
   * @param array $files Array of files
   *
   * @return string
   * @throws RuntimeException
   */
  function add($files) {
    return Util::exec("add", $files, array(), $this->path, true);
  }

  /**
   * @param array $files Array of files
   *
   * @return string
   */
  function revert(array $files = array()) {
    return Util::exec("revert", $files, array(), $this->path, true);
  }

  /**
   * @param string $to Destination path
   *
   * @return string
   */
  function sw($to) {
    return Util::exec("switch", $to, array(), $this->path, true);
  }

  /**
   * @param array  $paths            Paths
   * @param string $revision         Revision
   * @param bool   $ignore_externals Ignore Externals
   * @param int    $timeout          Timeout delay
   * @param bool   $output           Output
   *
   * @return string
   */
  function update(array $paths = array(), $revision = "HEAD", $ignore_externals = false, $timeout = 1800, $output = true) {
    $options = array(
      "--revision" => $revision,
    );

    if ($ignore_externals) {
      $options["--ignore-externals"] = true;
    }

    return Util::exec("update", $paths, $options, $this->path, $output, $timeout);
  }

  /**
   * @param string $file File
   *
   * @return string
   */
  function cleanup($file = '.') {
    return Util::exec("cleanup", $file);
  }

  /**
   * @param string $path       Path
   * @param null   $limit      Limit
   * @param bool   $verbose    Whether to print verbose data
   * @param bool   $stopOnCopy Whether to stop on copy or not
   *
   * @return string
   */
  function log($path = "", $limit = null, $verbose = false, $stopOnCopy = true) {
    $options = array(
      "--xml"          => true,
      "--verbose"      => $verbose,
      "--limit"        => (int)$limit,
      "--stop-on-copy" => $stopOnCopy,
    );

    return Util::exec("log", $path, $options, $this->path);
  }

  /**
   * @param string     $url      Url
   * @param string|int $revision Revision
   * @param bool       $verbose  Verbose
   *
   * @return string
   */
  function logRevision($url, $revision, $verbose = false) {
    $options = array(
      "--revision"  => $revision,
      "--xml"       => true,
      "--verbose"   => $verbose,
    );

    return Util::exec("log", $url, $options);
  }

  /**
   * @param string $file     File
   * @param null   $revision Revision
   *
   * @return string
   */
  function info($file = ".", $revision = null) {
    $options = array(
      "--xml" => true,
    );

    if ($revision) {
      $options['--revision'] = $revision;
    }

    return Util::exec("info", $file, $options, $this->path);
  }

  /**
   * @param string $file             File
   * @param bool   $ignore_externals Whether to ignore externals or not
   * @param bool   $show_updates     Whether to shop updates or not
   *
   * @return string
   */
  function status($file = ".", $ignore_externals = false, $show_updates = false) {
    $options = array(
      "--xml" => true,
    );

    if ($ignore_externals) {
      $options["--ignore-externals"] = true;
    }

    if ($show_updates) {
      $options["--show-updates"] = true;
    }

    return Util::exec("status", $file, $options, $this->path);
  }

  /**
   * @param string|int $old_rev         Old revision
   * @param string|int $new_rev         New revision
   * @param bool       $ignore_branches Whether to ignore branches
   * @param bool       $summarize       Summarize
   *
   * @return string
   */
  function diffUrl($old_rev, $new_rev, $ignore_branches = false, $summarize = false) {
    if ($summarize) {
      $options = array(
        "--xml" => true,
        "--summarize" => true,
      );
    }

    $root_url = $this->repository->getURL();
    $url = $ignore_branches ? "$root_url/trunk" : $root_url;
    $args = array("$url@$old_rev", "$url@$new_rev");

    return Util::exec("diff", $args, isset($options) ? $options : array(), $this->path);
  }

  /**
   * @param string $path Path
   * @param string $name Name
   *
   * @return string
   */
  function getProperty($path, $name) {
    return Util::exec("propget", array($name, $path), array(), $this->path);
  }

  /**
   * @param string $path  Path
   * @param string $name  Name
   * @param string $value Value
   *
   * @return string
   * @throws Exception
   */
  function setProperty($path, $name, $value) {
    $tempfile = tempnam("", "svn");
    file_put_contents($tempfile, $value);

    $options = array(
      "-F" => $tempfile,
    );

    try {
      $result = Util::exec("propset", array($name, $path), $options, $this->path);
    }
    catch (Exception $e) {
      unlink($tempfile);
      throw $e;
    }

    unlink($tempfile);

    return $result;
  }

  /**
   * @param string $path Path
   * @param string $name Name
   *
   * @return string
   */
  function removeProperty($path, $name) {
    return Util::exec("propdel", array($name, $path), array(), $this->path);
  }

  /**
   * @param string $path Path
   *
   * @return array
   */
  function listProperties($path) {
    $options = array(
      "--xml"     => true,
      "--verbose" => true,
    );

    $xml  = Util::exec("proplist", $path, $options, $this->path);
    $data = DOM::parse($xml);

    $properties = $data->xpath("//property");

    $list = array();
    foreach ($properties as $prop) {
      $list[$prop->getAttribute("name")] = trim($prop->textContent);
    }

    return $list;
  }
} 