<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deploy:MajAuto command
 */
class DeployMaJAuto extends DeployMaJ {

  protected $master_host;
  protected $username;
  protected $password;
  protected $master_name;
  protected $master_role;
  protected $server_id;

  protected $instances_not_allowed = array();
  protected $instances_outdated = array();
  protected $instances_already_outdated = array();

  protected $status;
  protected $report = '';
  protected $debug  = false;
  protected $dryrun = false;

  const STATUS_ERROR   = '0';
  const STATUS_OK      = '1';
  const STATUS_WARNING = '2';

  const INSTANCE_STATUS_KO          = '0';
  const INSTANCE_STATUS_OK          = '1';
  const INSTANCE_STATUS_OUTDATED    = '3';
  const INSTANCE_STATUS_UNAVAILABLE = '4';

  /**
   * @see parent::configure()
   */
  protected function configure() {
    $this
      ->setName('deploy:autoupdate')
      ->setAliases(array('deploy:au'))
      ->setDescription('Synchronize MB with RSYNC')
      ->setHelp('Performs an RSYNC command from MB Master')
      ->addOption(
        'path',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Working copy root',
        realpath(__DIR__ . "/../../../")
      )
      ->addOption(
        'allow_trunk',
        't',
        InputOption::VALUE_NONE,
        'Allow TRUNK working copy for MASTER'
      )
      ->addOption(
        'clear_cache',
        'c',
        InputOption::VALUE_NONE,
        'Performs a cache cleanup'
      )->addOption(
        'debug',
        'd',
        InputOption::VALUE_NONE,
        'Prints debug output also in console'
      )->addOption(
        'dry_run',
        'r',
        InputOption::VALUE_NONE,
        'Bypasses any local working copy modifications (svn, rsync, libs install)'
      );
  }

  /**
   * @see parent::showHeader()
   */
  protected function showHeader() {
    $this->start_time = microtime(true);
    $this->out($this->output, "Automatic update starting...");
  }

  /**
   * @param InputInterface  $input  InputInterface
   * @param OutputInterface $output OutputInterface
   *
   * @return int|void|null
   * @throws Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output      = $output;
    $this->input       = $input;
    $this->path        = $input->getOption('path');
    $this->allow_trunk = $input->getOption('allow_trunk');
    $this->clear_cache = $input->getOption('clear_cache');
    $this->debug       = $input->getOption('debug');
    $this->dryrun      = $input->getOption('dry_run');

    $this->status = self::STATUS_OK;

    // Lock file acquisition
    if (!$this->dryrun && !$this->acquire()) {
      $this->errorMsg('Instance is locked.', self::STATUS_ERROR, false);
    }

    $this->showHeader();

    if (!is_dir($this->path)) {
      $this->errorMsg("$this->path is not a valid directory", self::STATUS_ERROR, false);
    }

    if (!$this->getMasterBranch()) {
      $this->errorMsg('Exiting.', self::STATUS_ERROR, false);
    }

    $this->getConfig();

    $this->checkAllInstancesUpdateStatuses();
    $this->checkAllInstancesUpdatePermissions();
    $this->checkAllInstancesBranches();

    try {
      if ($this->shouldMasterUpdate()) {
        if (!$this->dryrun) {
          $this->doSVNCleanup();
          $this->doSVNRevert();
          $this->doSVNUpdate();
        }
        else {
          $this->out($this->output, 'Dry-run: Master update skipped..');
        }
      }
      else {
        $this->out($this->output, 'No instances were allowed to update, hence master update will not be performed...');
      }

      if (!$this->checkExternalsBranch()) {
        throw new Exception('Working copy and externals are not on the same branch.');
      }
    }
    catch (Exception $e) {
      $this->errorMsg($e, self::STATUS_ERROR);
    }

    $this->out($this->output, 'Performing operation...');

    // External libraries installation
    if (!$this->dryrun) {
      $this->installLibraries();
    }
    else {
      $this->out($this->output, 'Dry-run: Libraries installation skipped...');
    }

    try {
      $files = $this->getIncludedAndExcluded();

      if ($this->clear_cache) {
        // Flag cache file creation
        $clear_cache_file = "{$this->path}/tmp/clear_cache.flag";
        touch($clear_cache_file);
        chmod($clear_cache_file, 0755);

        // Adding flag file to RSYNC in order to propage it
        $files[] = array(
          'file' => $clear_cache_file,
          'dest' => 'tmp'
        );
      }

      foreach ($this->all_instances as $_instance) {
        $this->out($this->output, "======== " . $_instance['path'] . " ========");
        if ($this->shouldInstanceBeUpdated($_instance)) {
          if (!$this->dryrun) {
            $this->out($this->output, "Update allowed. Performing Rsync...");
            parent::rsync($files, $_instance['path']);
            $this->clearCache($_instance['path']);
          }
          else {
            $this->out($this->output, 'Dry-run: Rsync skipped...');
          }
        }
        else {
          $this->out($this->output, "Update not allowed.");
        }
      }

      /* Check statuses */
      if ($this->shouldMasterUpdate()) {
        $this->checkAllInstancesUpdateStatuses();
      }

      $this->release();
      $this->out($this->output, "Operation completed. Elapsed time: {$this->elapsed_time}");
      $this->sendReport();
    }
    catch (Exception $e) {
      $this->errorMsg($e, self::STATUS_ERROR);
    }
  }

  /**
   * @param OutputInterface $output OutputInterface
   * @param string          $text   Text
   *
   * @return void
   */
  protected function out(OutputInterface $output, $text) {
    $this->report .= "\n" . strftime("[%Y-%m-%d %H:%M:%S]") . " - $text";
    if ($this->debug) {
      parent::out($output, $text);
    }
  }

  /**
   * @param string $msg          Message
   * @param string $status_code  Status code
   * @param bool   $release_lock Release lock value
   *
   * @return void
   */
  protected function errorMsg($msg, $status_code, $release_lock = true) {
    $this->status = $status_code;

    if ($release_lock) {
      $this->release();
    }

    $this->out($this->output, $msg);
    $this->sendReport();
    exit(0);
  }

  /**
   * @return void
   */
  protected function getConfig() {
    $this->rsyncupdate_conf = "$this->path/cli/conf/deploy.xml";

    if (!is_readable($this->rsyncupdate_conf)) {
      $this->errorMsg("$this->rsyncupdate_conf is not readable.", self::STATUS_ERROR);
    }

    $this->rsyncupdate_dom = new DOMDocument();
    if (!$this->rsyncupdate_dom->load($this->rsyncupdate_conf)) {
      $this->errorMsg("Failed to load $this->rsyncupdate_conf DOMDocument.", self::STATUS_ERROR);
    }

    $this->rsyncupdate_xpath = new DOMXPath($this->rsyncupdate_dom);

    $this->getRemoteConfig();
    $this->getMasterConfig();
    $this->loadInstancesData();
  }

  /**
   * @param array  $array  Array of instances
   * @param string $id_key String representing ids key in first argument array
   *
   * @return array
   */
  private function getInstancesIds($array, $id_key = 'id') {

    $ids_array = array();
    foreach ($array as $item) {
      if (array_key_exists($id_key, $item) && !empty($item[$id_key])) {
        $ids_array[] = $item[$id_key];
      }
    }
    return array_unique($ids_array);
  }

  /**
   * @param array $outdated_modules Outdated modules array
   * @return void
   */
  protected function displayUpdateStatus($outdated_modules = array()) {
    /* Refer to script that builds the update status object : monitorClient/get_update_status.php */
    if (count($outdated_modules)) {
      $this->out($this->output, "INFO: ".count($outdated_modules)." modules are outdated.");
    }
    else {
      $this->out($this->output, "INFO: All modules are up to date.");
    }
    foreach ($outdated_modules as $module_name => $module_status) {
      if (array_key_exists('code', $module_status)) {
        $this->out($this->output, "[$module_name] (".$module_status['code']['current']." --> ".$module_status['code']['latest'].")");
      }
      else {
        $this->out($this->output, "[$module_name]");
      }
      if (array_key_exists('dsn', $module_status)) {
        $this->out($this->output, "There are ".count($module_status['dsn'])." outdated datasources :");
        foreach ($module_status['dsn'] as $outdated_datasource) {
          $this->out($this->output, $outdated_datasource);
        }
      }
    }
  }

  /**
   * @return bool
   */
  protected function shouldMasterUpdate() {
    foreach ($this->all_instances as $_instance) {
      if ($_instance['update_allowed'] === true) {
        return true;
      }
    }
    return false;
  }

  /**
   * @return void
   */
  protected function getRemoteConfig() {
    /** @var DOMElement $monitoring */
    $monitoring = $this->rsyncupdate_xpath->query("(/groups/monitoring)[1]")->item(0);

    if (!$monitoring) {
      $this->errorMsg('Cannot find server configuration.', self::STATUS_ERROR);
    }

    $this->master_host = rtrim($monitoring->getAttribute('url'), '/') . '/';
    $this->username    = $monitoring->getAttribute('username');
    $this->password    = $monitoring->getAttribute('password');

    if (!$this->master_host || !$this->username || !$this->password) {
      $this->errorMsg('Cannot find server configuration.', self::STATUS_ERROR);
    }
  }

  /**
   * @return array|bool
   */
  protected function loadInstancesData() {
    /** @var DOMNodeList $instances */
    $instances = $this->rsyncupdate_xpath->query("//instance");

    if (!$instances) {
      $this->errorMsg('No configured instance.', self::STATUS_WARNING);
    }

    $this->all_instances = array();

    /** @var DOMElement $_instance */
    foreach ($instances as $_instance) {

      $_token_hash = false;
      $_group = $_instance->parentNode;
      if ($_group instanceof DOMElement) {
        $_token_hash  = $_group->getAttribute("token_hash");
      }

      $_ip_adress    = 'localhost';
      $_shortname    = $_instance->getAttribute("shortname");
      $_path         = $_instance->getAttribute("path");
      $_web_path     = $_instance->getAttribute("web_path");
      $_instance_id  = intval($_instance->getAttribute("id"));
      $_server_id    = $_instance->getAttribute("server_id");
      $_no_webserver = $_instance->getAttribute("no_webserver");

      if (preg_match('/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/', $_path, $match)) {
        $_ip_adress = $match[0];
      }

      $this->all_instances[$_shortname] = array(
        'shortname'      => $_shortname,
        'id'             => $_instance_id,
        'server_id'      => $_server_id,
        'path'           => $_path,
        'release_code'   => false,
        'perform'        => false,
        'status'         => self::INSTANCE_STATUS_KO,
        'update_allowed' => false,
        'ip_adress'      => $_ip_adress,
        'token_hash'     => $_token_hash,
        'target_url'     => $_ip_adress.(!empty($_web_path) ? trim($_web_path) : '/'.basename($_path)),
        'no_webserver'   => !empty($_no_webserver)
      );
    }

    if (empty($this->all_instances)) {
      $this->errorMsg('No configured instance.', self::STATUS_WARNING);
      return false;
    }

    return $this->all_instances;
  }

  /**
   * @param string      $baseUrl           Base URL
   * @param null|string $access_token_hash Access Token Hash
   *
   * @return bool|string
   */
  protected function checkInstanceForUpdate($baseUrl, $access_token_hash = null) {
    $this->out($this->output, "Checking instance for update: {$baseUrl}");
    $url = "http://{$baseUrl}/index.php" . "?" . http_build_query(
      array(
        "token" => $access_token_hash,
        "m"     => 'monitorClient',
        'raw'   => 'get_update_status',
      ),
      "",
      "&"
    );

    $http_client = curl_init($url);

    curl_setopt($http_client, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_TIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($http_client, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($http_client, CURLOPT_POST, false);

    $response = curl_exec($http_client);
    $http_code = curl_getinfo($http_client, CURLINFO_HTTP_CODE);

    if ($http_code === 200) {
      $this->out($this->output, "Instance update status successfully received ! (http_code: $http_code)");
    }
    else {
      $this->out($this->output, "Getting instance update status failed ! (http_code: $http_code)");
      $this->out($this->output, "Url: $url");
      if (curl_errno($http_client)) {
        $this->out($this->output, curl_error($http_client));
      }
      else {
        $this->out($this->output, $response);
      }
    }

    return $response;
  }
  /**
  * @description In order to avoid concurrent calls to server
  * @return string $response
  */
  protected function checkRemoteAuthorization() {
    sleep(rand(1, 10));

    $this->out($this->output, "Checking remote authorization...");
    $this->out($this->output, "Communicating with {$this->master_host}...");

    $url = $this->master_host . "?" . http_build_query(
      array(
        "m"               => "monitorServer",
        "a"               => "check_remote_authorization",
        "login"           => "1",
        "suppressHeaders" => "1",
        "username"        => $this->username,
        "password"        => $this->password
      )
      , "", "&"
    );

    $http_client = curl_init($url);
    $instance_ids = $this->getInstancesIds($this->all_instances);

    curl_setopt($http_client, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_TIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($http_client, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($http_client, CURLOPT_POST, true);
    curl_setopt($http_client, CURLOPT_POSTFIELDS, json_encode($instance_ids));

    $response = curl_exec($http_client);
    $http_code = curl_getinfo($http_client, CURLINFO_HTTP_CODE);

    if ($http_code === 200) {
      $this->out($this->output, "Remote authorization check successfully received ! (http_code: $http_code)");
      $this->out($this->output, $response);
    }
    else {
      $this->out($this->output, "Getting remote authorization check failed ! (http_code: $http_code)");
      $this->out($this->output, "Url: $url");
      $this->out($this->output, curl_error($http_client));
    }

    return $response;
  }

  /**
   * @return void
   */
  protected function doSVNRevert() {
    $this->out($this->output, "Checking SVN status...");
    $out = $this->wc->status(".", $this->ignore_externals);

    if ($out) {
      $this->out($this->output, "SVN status checked.");

      $modified_files = array();
      try {
        $modified_files = $this->getModifiedFilesByXML($out);
      }
      catch (Exception $e) {
        $this->errorMsg('Cannot parse modified files by XML.', self::STATUS_ERROR);
      }

      if ($modified_files) {
        $this->out($this->output, "These files are modified locally:");

        foreach ($modified_files as $_file) {
          $this->out($this->output, $_file);
        }

        // Files have to be reverted
        $this->out($this->output, "Reverting files...");

        try {
          // /!\ Long list may handle an exception
          $this->wc->revert($modified_files);
        }
        catch (Exception $e) {
          $this->errorMsg($e, self::STATUS_ERROR);
        }

        $this->out($this->output, "Files reverted.");
      }
    }
  }

  /**
   * @throws Exception
   * @return void
   */
  protected function doSVNUpdate() {
    $this->out($this->output, "SVN update in progress...");

    $this->wc->update(array(), "HEAD", $this->ignore_externals);

    $this->out($this->output, "SVN update completed.");

    $this->installComposerDependencies();

    // SVN status file writing
    $this->writeSVNStatusFile();
  }

  /**
   * @see parent::checkFilePresence
   */
  protected function checkFilePresence($result) {
    return (preg_match_all("#(" . implode("|", $this->patterns_to_check) . ")#m", $result, $matches));
  }

  /**
   * @param bool|string $_instance Instance path
   *
   * @return bool|string
   */
  protected function clearCache($_instance = false) {
    if (!$_instance) {
      return false;
    }

    if ($this->clear_cache) {
      $token_hash = null;

      /* Try to get token from browsing instances data which was extracted from deploy.xml */
      foreach ($this->all_instances as $instance) {
        if ($_instance === $instance['path']
            && array_key_exists('token_hash', $instance)
            && !empty($instance['token_hash'])
        ) {
          $token_hash = $instance['token_hash'];
        }
      }

      if (!$token_hash) {
        $msg          = "$_instance - Clear cache token is not configured !";
        $this->status = self::STATUS_WARNING;
        $this->out($this->output, $msg);
        return false;
      }

      /* Clear the cache */
      $this->out($this->output, "$_instance - Clearing cache...");

      $ip_addresses = $this->getIPAddresses($this->instances_to_perform);
      $url = $ip_addresses[$_instance] . '/' . basename($_instance);

      $url = "http://{$url}/index.php" . "?" . http_build_query(
        array(
          "token" => $token_hash,
          "m"     => 'monitorClient',
          'raw'   => 'cache_clear',
          'keys'  => 'mandatory'
        ),
        "",
        "&"
      );

      $http_client = curl_init($url);

      curl_setopt($http_client, CURLOPT_CONNECTTIMEOUT, 5);
      curl_setopt($http_client, CURLOPT_TIMEOUT, 10);
      curl_setopt($http_client, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($http_client, CURLOPT_FOLLOWLOCATION, true);

      $response = curl_exec($http_client);
      $http_code = curl_getinfo($http_client, CURLINFO_HTTP_CODE);

      if ($http_code === 200) {
        $msg = "$_instance - Locales cache cleared. (http_code: $http_code)";
        //$msg .= "\n" . $response;
      }
      else {
        $msg = "$_instance - Unable to clear locales cache ! (http_code: $http_code)";
        if (curl_errno($http_client)) {
          $msg .= "\n" . curl_error($http_client);
        }
        else {
          $msg .= "\n" . $response;
        }
        $this->status = self::STATUS_WARNING;
      }

      $this->out($this->output, $msg);
      return $response;
    }

    return false;
  }

  /**
   * @param array $array Array of instances
   *
   * @return array
   */
  protected function getIPAddresses($array) {
    $ip_addresses = array();
    foreach ($array as $_path) {
      $ip_addresses[$_path] = 'localhost';
      if (preg_match('/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/', $_path, $match)) {
        $ip_addresses[$_path] = $match[0];
      }
    }

    return $ip_addresses;
  }

  /**
   * @return void
   */
  protected function getMasterConfig() {
    /** @var DOMElement $master */
    $master = $this->rsyncupdate_xpath->query("(/groups/master)[1]")->item(0);

    if (!$master) {
      $this->errorMsg('Cannot find master configuration.', self::STATUS_ERROR);
    }

    $this->master_name = $master->getAttribute('name');
    $this->master_role = $master->getAttribute('role');
    $this->server_id   = $master->getAttribute('server_id');

    if (!$this->master_name || !$this->master_role || !$this->server_id) {
      $this->errorMsg('Cannot find master configuration.', self::STATUS_ERROR);
    }
  }

  /**
   * @return bool|string
   */
  protected function sendReport() {
    $url = $this->master_host . "?" . http_build_query(
      array(
        "m"               => "monitorServer",
        "a"               => "get_auto_update_report",
        "login"           => "1",
        "suppressHeaders" => "1",
        "username"        => $this->username,
        "password"        => $this->password
      )
      , "", "&"
    );

    $http_client = curl_init($url);
    $report_data = $this->makeReport();

    curl_setopt($http_client, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($http_client, CURLOPT_TIMEOUT, 10);
    curl_setopt($http_client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($http_client, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($http_client, CURLOPT_POST, true);
    curl_setopt($http_client, CURLOPT_POSTFIELDS, json_encode($report_data));

    $response  = curl_exec($http_client);
    $http_code = curl_getinfo($http_client, CURLINFO_HTTP_CODE);

    /* Console output only from here since report was sent so it won't get the following logs */
    if ($http_code === 200) {
      $this->out($this->output, "Report successfully sent ! (http_code: $http_code)");
    }
    else {
      $this->out($this->output, "Sending report failed ! (http_code: $http_code)");
      $this->out($this->output, "Url: $url");
      if (curl_errno($http_client)) {
        $this->out($this->output, curl_error($http_client));
      }
      else {
        $this->out($this->output, $response);
      }
    }

    return $response;
  }

  /**
   * @return array
   */
  protected function makeReport() {

    $instances = array(
      'updated'     => array(),
      'skipped'     => array(),
      'outdated'    => array(),
      'unavailable' => array()
    );

    foreach ($this->all_instances as $_instance) {
      switch ($_instance['status']) {
        case self::INSTANCE_STATUS_KO:
          $_status = 'skipped';
          break;
        case self::INSTANCE_STATUS_OK:
          $_status = 'updated';
          break;
        case self::INSTANCE_STATUS_UNAVAILABLE:
          $_status = 'unavailable';
          break;
        case self::INSTANCE_STATUS_OUTDATED:
          $_status = 'outdated';
          break;
        default:
          $_status = 'skipped';
          break;
      }
      $instances[$_status][] = array(
        'instance_id'    => $_instance['id'],
        'server_id'      => $_instance['server_id'],
        'update_allowed' => $_instance['update_allowed']
      );
    }

    $data = array(
      'id'                       => "{$this->server_id}-{$this->master_name}",
      'date'                     => date('Y/m/d H:i:s', $this->start_time),
      'server_id'                => $this->server_id,
      'instance_ids_ok'          => $instances['updated'],
      'instance_ids_ko'          => $instances['skipped'],
      'instance_ids_outdated'    => $instances['outdated'],
      'instance_ids_unavailable' => $instances['unavailable'],
      'role'                     => $this->master_role,
      'status'                   => $this->status,
      'elapsed_time'             => $this->elapsed_time,
      'branch'                   => str_replace('_', '/', $this->master_branch),
      'body'                     => trim($this->report)
    );

    return $data;
  }

  /**
   * @return void
   */
  protected function checkAllInstancesUpdateStatuses() {

    $this->out($this->output, "Calling all instances to get instance status...");

    foreach ($this->all_instances as $_instance) {

      /* Bypass instance status check via http if instance is marked as having no webserver */
      if (true === $_instance['no_webserver']) {
        $this->all_instances[$_instance['shortname']]['status'] = self::INSTANCE_STATUS_UNAVAILABLE;
        $this->out(
          $this->output,
          $_instance['id']." - Instance update status is not available, since no webserver if configured on this instance."
        );
        continue;
      }

      if (!empty($_instance['token_hash'])) {
        $instance_update_status = $this->checkInstanceForUpdate(
          $_instance['target_url'],
          $_instance['token_hash']
        );

        $instance_update_status_object = json_decode($instance_update_status, true);

        if (!is_array($instance_update_status_object)) {
          $this->status = self::STATUS_WARNING;
          $this->all_instances[$_instance['shortname']]['status'] = self::INSTANCE_STATUS_KO;
          $this->out($this->output, $_instance['id']." - Instance update status request failed, aborting update !");
        }
        else {
          if (count($instance_update_status_object) === 0) {
            /* Consider this instance ready for update, since update status request succeded and reported nothing */
            $this->all_instances[$_instance['shortname']]['status'] = self::INSTANCE_STATUS_OK;
            $this->out($this->output, $_instance['id']." - Instance is up to date !");
          }
          else {
            if (!is_array($instance_update_status_object) || !empty($instance_update_status_object)) {
              $this->all_instances[$_instance['shortname']]['status'] = self::INSTANCE_STATUS_OUTDATED;
              $this->displayUpdateStatus($instance_update_status_object);
            }
          }
        }
      }
      else {
        $this->status = self::STATUS_WARNING;
        $_instance['status'] = self::INSTANCE_STATUS_KO;
        $this->errorMsg($_instance['shortname']." - Token hash was NOT found, aborting update !", self::STATUS_WARNING);
      }
    }
  }

  /**
   * @return void
   */
  protected function checkAllInstancesUpdatePermissions() {

    $permissions = json_decode($this->checkRemoteAuthorization(), true);

    $this->out($this->output, 'Response received.');

    if (!is_array($permissions)) {
      $this->errorMsg("Invalid data: {$permissions}", self::STATUS_WARNING);
    }

    foreach ($permissions as $_instance_id => $_instance_permission) {
      $_instance_update_allowed = false;
      /* @see Refer to file monitorServer/check_remote_authorization.php for authorization status codes */
      switch ($_instance_permission) {
        case 1:
        case 3:
          $_instance_update_allowed = true;
          $this->out($this->output, "Instance #{$_instance_id} is allowed to update.");
          break;
        default:
          $this->out($this->output, "Instance #{$_instance_id} is not allowed to update.");
          break;
      }

      foreach ($this->all_instances as $_instance) {
        if ($_instance['id'] === $_instance_id) {
          $this->all_instances[$_instance['shortname']]['update_allowed'] = $_instance_update_allowed;
        }
      }
    }
  }

  /**
   * @return void
   */
  protected function checkAllInstancesBranches() {
    $this->out($this->output, "Checking branches...");
    $instances_branches = array();

    foreach ($this->all_instances as $_instance) {
      $instances_branches[] = $_instance['path'];
    }

    $instances_checked = array();
    try {
      $instances_checked = $this->checkBranches($instances_branches);
    }
    catch (Exception $e) {
      $this->errorMsg($e, self::STATUS_ERROR);
    }

    foreach ($instances_checked as $_instance_checked) {
      foreach ($this->all_instances as $_instance) {
        if ($_instance_checked['path'] === $_instance['path']) {
          $this->all_instances[$_instance['shortname']]['release_code'] = $_instance_checked['release_code'];
          $this->all_instances[$_instance['shortname']]['perform'] = $_instance_checked['perform'];
        }
      }
    }
  }

  /**
   * @param array $instance Instance array
   *
   * @return bool
   */
  protected function shouldInstanceBeUpdated($instance) {
    return $instance['perform'] === true && $instance['release_code'] !== false && $instance['update_allowed'] === true;
  }
}
