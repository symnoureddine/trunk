<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Cron;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CModelObject;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;

/**
 * Cronjob log
 */
class CCronJobLog extends CMbObject {
  const SEVERITY_NONE = 0;
  const SEVERITY_INFO = 1;
  const SEVERITY_WARNING = 2;
  const SEVERITY_ERROR = 3;

  static $error_map = array(
    self::SEVERITY_NONE    => '',
    self::SEVERITY_INFO    => 'INFO',
    self::SEVERITY_WARNING => 'WARNING',
    self::SEVERITY_ERROR   => 'ERROR',
  );

  /** @var integer Primary key */
  public $cronjob_log_id;

  public $cronjob_id;

  public $status;
  public $log;
  public $severity;

  public $start_datetime;
  public $end_datetime;
  public $duration;
  public $server_address;

  /** @var CCronJob */
  public $_ref_cronjob;

  public $_duration;

  public $_date_min;
  public $_date_max;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec           = parent::getSpec();
    $spec->table    = "cronjob_log";
    $spec->key      = "cronjob_log_id";
    $spec->loggable = false;

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    // Keep the old duration for now
    if (!$this->duration && $this->end_datetime && $this->start_datetime) {
      $this->_duration = CMbDT::timeRelative($this->start_datetime, $this->end_datetime);
    }
  }

  /**
   * @inheritdoc
   */
  public function store() {
    /* Possible purge when creating a CCronJobLog */
    if (!$this->_id) {
      CApp::doProbably(CAppUI::conf('CCronJobLog_purge_probability'), array('CCronJobLog', 'purgeSome'));
    }

    return parent::store();
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();

    $props["status"]         = "str notNull";
    $props["log"]            = "text";
    $props["severity"]       = "enum notNull list|0|1|2|3 default|0";
    $props["cronjob_id"]     = "ref class|CCronJob notNull autocomplete|name back|cron_logs cascade";
    $props["start_datetime"] = "dateTime notNull";
    $props["end_datetime"]   = "dateTime";
    $props["server_address"] = "str";
    $props["duration"]       = "num";

    //filter
    $props["_date_min"] = "dateTime";
    $props["_date_max"] = "dateTime";

    $props["_duration"] = "str";

    return $props;
  }

  /**
   * Load the cronjob
   *
   * @return CCronJob|null
   * @throws Exception
   */
  function loadRefCronJob() {
    return $this->_ref_cronjob = $this->loadFwdRef("cronjob_id");
  }

  /**
   * Purge the CCronJobLog older than the configured delay
   *
   * @return bool|resource|void
   * @throws Exception
   */
  public static function purgeSome() {
    if (!$delay = CAppUI::conf('CCronJobLog_purge_delay')) {
      return null;
    }

    $date  = CMbDT::dateTime("- {$delay} days");
    $limit = (CAppUI::conf('CCronJobLog_purge_probability') ?: 1000) * 10;

    $ds = CSQLDataSource::get('std');

    $query = new CRequest();
    $query->addTable('cronjob_log');
    $query->addWhere(
      array(
        'end_datetime' => $ds->prepare('IS NOT NULL AND `end_datetime` < ?', $date)
      )
    );
    $query->setLimit($limit);

    return $ds->exec($query->makeDelete());
  }

  /**
   * Log a message, with a severity
   *
   * @param string $msg      Message
   * @param mixed  $data     Additionnal data to log
   * @param int    $severity Severity
   *
   * @return mixed
   */
  public static function logMessage($msg, $data = null, $severity = self::SEVERITY_NONE) {
    $cron_log_id = CApp::getCronLogId();

    $cache = new Cache(__CLASS__, $cron_log_id, Cache::INNER_OUTER);
    $messages = $cache->get() ?: [];

    $messages[] = [CMbDT::dateTime(), $severity, $msg, $data];

    return $cache->put($messages);
  }

  /**
   * Log an info message
   *
   * @param string $msg  Message
   * @param mixed  $data Additionnal data to log
   *
   * @return mixed
   */
  public static function logInfo($msg, $data = null) {
    return self::logMessage($msg, $data, self::SEVERITY_INFO);
  }

  /**
   * Log a warning message
   *
   * @param string $msg  Message
   * @param mixed  $data Additionnal data to log
   *
   * @return mixed
   */
  public static function logWarning($msg, $data = null) {
    return self::logMessage($msg, $data, self::SEVERITY_WARNING);
  }

  /**
   * Log an error message
   *
   * @param string $msg  Message
   * @param mixed  $data Additionnal data to log
   *
   * @return mixed
   */
  public static function logError($msg, $data = null) {
    return self::logMessage($msg, $data, self::SEVERITY_ERROR);
  }

  /**
   * Set the cron job log for storage purpose
   *
   * @return void
   * @throws Exception
   */
  public static function storeLog() {
    $cron_log_id = CApp::getCronLogId();

    if (!$cron_log_id) {
      return;
    }

    $job_log = new CCronJobLog();
    $job_log->load($cron_log_id);

    $job_log->severity = 0;
    $job_log->log      = '';

    $cache = new Cache(__CLASS__, $cron_log_id, Cache::INNER_OUTER);

    if ($messages = $cache->get()) {
      $log = array();
      foreach ($messages as $_message) {
        list($date, $sev, $msg, $data) = $_message;

        $log[] = sprintf("[%s] %s - %s", $date, CMbArray::get(self::$error_map, $sev, ''), $msg);

        // Handle loggin of objects and arrays
        if ($data) {
          if (is_object($data) && $data instanceof CModelObject) {
            $data = array(get_class($data) => $data->getPlainFields());
          }

          if (is_array($data) || is_object($data)) {
            $data = print_r($data, true);
          }

          $log[] .= " : " . $data;
        }

        $job_log->severity = max($job_log->severity, $sev);
      }

      $job_log->log = implode("\n", $log);
    }

    // Do not update status here because of cache
    $job_log->status = null;

    $job_log->store();

    $cache->rem();
  }
}
