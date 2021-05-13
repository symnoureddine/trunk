<?php
/**
 * @package Mediboard\Includes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Chronometer;
use Ox\Core\CRedisClient;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Mediboard\System\CAccessLog;
use Ox\Mediboard\System\CDataSourceLog;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CModuleAction;

/**
 * Access logging
 */
if (function_exists("apache_setenv")) {
  apache_setenv("PHP_SESS_ID", CSessionHandler::getSessionId());
}

if (CApp::isReadonly() || !CAppUI::conf("log_access")) {
  return;
}

global $m, $a, $action, $dosql;

$_action = CValue::first($dosql, $action, $a);

// $_action may not defined when the module is inactive
if (!$_action) {
  return;
}

// Check prerequisites
$ds = CSQLDataSource::get("std");

// fresh install case
if(!$ds->hasTable('module_action')){
  return;
}

// Key initialisation
$log                   = new CAccessLog();
$log->module_action_id = CModuleAction::getID($m, $_action);

// 10-minutes period aggregation
// Don't CMbDT::datetime() to get rid of CAppUI::conf("system_date") if ever used
$period      = strftime("%Y-%m-%d %H:%M:00");
$period[15]  = "0";
$log->period = $period;

// 10 minutes granularity
$log->aggregate = 10;

list($acquire_duration, $read_duration) = CSessionHandler::getDurations();
$log->session_read = round($read_duration, 3);
$log->session_wait = round($acquire_duration, 3);

// One hit
$log->hits++;

// Keep the scalar conversion
$log->bot = CApp::$is_robot ? 1 : 0;

// Stop chrono if not already done
$chrono = CApp::$chrono;
if ($chrono->step > 0) {
  $chrono->stop();
}
$log->duration += round((float)$chrono->total, 3);

// Redis stats
$redis_chrono = CRedisClient::$chrono;
if ($redis_chrono) {
  $log->nosql_time     = round((float)$redis_chrono->total, 3);
  $log->nosql_requests = $redis_chrono->nbSteps;
}

// System probes
$rusage           = getrusage();
$log->processus   += round((float)$rusage["ru_utime.tv_usec"] / 1000000 + $rusage["ru_utime.tv_sec"], 3);
$log->processor   += round((float)$rusage["ru_stime.tv_usec"] / 1000000 + $rusage["ru_stime.tv_sec"], 3);
$log->peak_memory += memory_get_peak_usage();

// SQL stats
foreach (CSQLDataSource::$dataSources as $_datasource) {
  if ($_datasource) {
    $log->request     += round((float)$_datasource->chrono->total, 3);
    $log->nb_requests += $_datasource->chrono->nbSteps;
  }
}

// Transport tiers
/**
 * @var string      $_tiers
 * @var Chronometer $_chrono
 */
foreach (CExchangeSource::$call_traces as $_tiers => $_chrono) {
  $log->transport_tiers_nb   = $_chrono->nbSteps;
  $log->transport_tiers_time = $_chrono->total;
}

// Bandwidth
$log->size += CApp::getOuputBandwidth();
//$log->other_bandwidth += CApp::getOtherBandwidth();

// Error log stats
$log->errors   += CApp::$performance["error"];
$log->warnings += CApp::$performance["warning"];
$log->notices  += CApp::$performance["notice"];

CAccessLog::$_current = $log;

CAccessLog::bufferize(array($log));

if (!CAppUI::conf("log_datasource_metrics")) {
  return;
}

$dslogs = array();
foreach (CSQLDataSource::$dataSources as $_datasource) {
  if ($_datasource) {
    //if ($_datasource->chrono->nbSteps === 0) {
    //  trigger_error("Datasource '$_datasource->dsn' did not execute any query", E_USER_NOTICE);
    //}

    $dslog                   = new CDataSourceLog();
    $dslog->module_action_id = $log->module_action_id;
    $dslog->datasource       = $_datasource->dsn;
    $dslog->requests         = $_datasource->chrono->nbSteps;
    $dslog->duration         = round((float)$_datasource->chrono->total, 3);
    $dslog->period           = $log->period;
    $dslog->aggregate        = $log->aggregate;
    $dslog->bot              = $log->bot;

    if ($_datasource->link) {
      // Measure network latency
      $t = microtime(true);

      if ($_datasource->ping()) {
        $latency = (microtime(true) - $t) * 1000;

        $_datasource->latency = $latency;
        $dslog->ping_duration = $latency;
      }

      $dslog->connection_time = $_datasource->connection_time;
      $dslog->connections     = 1;
    }

    $dslogs[] = $dslog;
  }
}

if ($redis_chrono) {
  $dslog                   = new CDataSourceLog();
  $dslog->module_action_id = $log->module_action_id;
  $dslog->datasource       = 'redis';
  $dslog->requests         = $redis_chrono->nbSteps;
  $dslog->duration         = round((float)$redis_chrono->total, 3);
  $dslog->period           = $log->period;
  $dslog->aggregate        = $log->aggregate;
  $dslog->bot              = $log->bot;

  $dslogs[] = $dslog;
}

CDataSourceLog::bufferize($dslogs);


