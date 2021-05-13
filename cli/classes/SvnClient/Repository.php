<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\SvnClient;

class Repository {
  /** @var string */
  private $url;

  /**
   * Get repository URL
   *
   * @return string
   */
  public function getURL() {
    return $this->url;
  }

  /**
   * Get UUID
   *
   * @return string
   */
  public function getUUID() {
    return $this->uuid;
  }

  /** @var string */
  private $uuid;

  function __construct($url, $uuid = null) {
    $this->url = $url;
    $this->uuid = $uuid;

    if (!$uuid) {
      $options = array(
        "--xml" => true,
      );
      $xml = Util::exec("info", $url, $options);

      $data = Util::parseXML($xml);
      $this->uuid = (string)$data->repository->uuid;
    }
  }

  protected function listDirectory($dir) {
    $options = array(
      "--xml" => true,
    );

    $xml = Util::exec("list", "$this->url/$dir", $options);
    $data = Util::parseXML($xml);

    $directories = array();
    foreach ($data->entry as $entry) {
      $commit = $entry->commit;
      $directories[] = array(
        "name"     => (string)$entry->name,
        "revision" =>    (int)$commit->attributes()->revision,
        "date"     => (string)$commit->date,
        "author"   => (string)$commit->author,
      );
    }

    return $directories;
  }

  // Get folders
  function getBranches(){
    return $this->listDirectory("branches");
  }

  function getTags(){
    return $this->listDirectory("tags");
  }

  function branch($branch_name, $message = null) {
    $options = array(
      "-m" => $message,
    );

    $url = $this->getURL();
    $url = rtrim($url, '/\\');
    return Util::exec('copy', array("$url/trunk", "$url/branches/$branch_name"), $options);
  }
} 