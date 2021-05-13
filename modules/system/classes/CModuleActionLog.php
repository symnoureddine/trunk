<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Exception;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\Chronometer;
use Ox\Core\CLogger;
use Ox\Core\CMbDebug;
use Ox\Core\CMbDT;
use Ox\Core\CStoredObject;

/**
 * Abastraction layer for access logs and data source logs
 * @todo Most content is yet to be abstract
 */
class CModuleActionLog extends CStoredObject {
  static $query_time;

  static $_classes = array(
    'CAccessLog',
    'CAccessLogArchive',
    'CDataSourceLog',
    'CDataSourceLogArchive',
  );

  /**
   * Among plain fields get non summable log signature fields
   *
   * @return array()
   */
  static function getSignatureFields() {
    return array();
  }

  /**
   * Gets summable plain fields, minus signature fields and key
   *
   * @return array
   */
  static function getValueFields() {
    $self = new static;

    $fields = $self->getPlainFields();
    unset($fields[$self->_spec->key]);

    return array_diff(array_keys($fields), $self->getSignatureFields());
  }

  /**
   * Fast store for multiple access logs using ON DUPLICATE KEY UPDATE MySQL feature
   *
   * @param self[] $logs Logs to be stored
   *
   * @return string Store-like message
   */
  static function fastMultiStore($logs, &$chrono = null) {
    if (!count($logs)) {
      return null;
    }

    /** @var self $self */
    $self = new static;

    // Columns to update
    $updates = array();

    $fields = $self->getPlainFields();
    unset($fields[$self->_spec->key]);
    $columns = array_keys($fields);

    foreach ($self->getValueFields() as $_name) {
      $updates[] = "$_name = $_name + VALUES($_name)";
    }

    // Values
    $values = array();
    foreach ($logs as $_log) {
      $row = array();

      foreach (array_keys($fields) as $_name) {
        $value = $_log->$_name;
        $row[] = "'$value'";
      }

      $row      = implode(", ", $row);
      $row      = "($row)";
      $values[] = $row;
    }

    $columns = implode(", ", $columns);
    $updates = implode(", ", $updates);
    $values  = implode(",\n", $values);

    $table = $self->_spec->table;
    $query = "INSERT INTO $table ($columns)
      VALUES \n$values
      ON DUPLICATE KEY UPDATE $updates";

    $ds = $self->_spec->ds;

    if (!$ds->exec($query)) {
      return $ds->error();
    }

    if (!is_null($chrono)) {
      $chrono += $ds->chrono->latestStep;
    }

    return null;
  }

  /**
   * Assemble logs based on logical key fields
   *
   * @param self[] $logs Raw access log collection
   *
   * @return self[] $logs Assembled access log collection
   */
  static function assembleLogs($logs) {
    $signature_fields = static::getSignatureFields();
    $value_fields     = static::getValueFields();

    $assembled_logs = array();
    foreach ($logs as $_log) {
      // Signature values
      $signature_values = array();

      foreach ($signature_fields as $_field) {
        $signature_values[] = $_log->$_field;
      }

      // Make signature
      $signature = implode(",", $signature_values);

      // First log for this signature
      if (!isset($assembled_logs[$signature])) {
        $assembled_logs[$signature]      = $_log;
        $assembled_logs[$signature]->_id = null;
        continue;
      }

      // Assembling (summing) other log for the same signature
      $log = $assembled_logs[$signature];

      foreach ($value_fields as $_name) {
        $log->$_name += $_log->$_name;
      }
    }

    return $assembled_logs;
  }

  /**
   * Put logs in buffer and store them.
   * Use direct storage if buffer_life time config is 0
   *
   * @param self[] $logs Log collection to put in buffer
   *
   * @return void
   */
  static function bufferize($logs) {
    /** @var CModuleActionLog $class */
    $class = get_called_class();
    $class = CClassMap::getInstance()->getShortName($class);

    // No buffer use standard unique fast store
    $buffer_lifetime = CAppUI::conf("access_log_buffer_lifetime");
    if (!$buffer_lifetime) {
      if ($msg = static::fastMultiStore($logs)) {
        CApp::log("Could not store logs: $msg", $class, CLogger::LEVEL_DEBUG);
        trigger_error($msg, E_USER_WARNING);
      }

      return;
    }

    // Buffer logs into file
    $buffer = CAppUI::getTmpPath("$class.buffer");
    foreach ($logs as $_log) {
      file_put_contents($buffer, serialize($_log) . PHP_EOL, FILE_APPEND);
    }

    // Unless lifetime is reached by random, don't unbuffer logs
    if (rand(1, $buffer_lifetime) !== 1) {
      return;
    }

    // Move to temporary buffer to prevent concurrent unbuffering
    $tmpbuffer = tempnam(dirname($buffer), basename($buffer) . ".");
    if (!rename($buffer, $tmpbuffer)) {
      // Keep the log for a while, should not be frequent with buffer lifetime 100+
      CApp::log("Probable concurrent logs unbuffering", $class, CLogger::LEVEL_DEBUG);

      return;
    }

    // Read lines from temporary buffer
    $lines         = file($tmpbuffer);
    $buffered_logs = array();
    foreach ($lines as $_line) {
      $buffered_logs[] = unserialize($_line);
    }

    $assembled_logs = static::assembleLogs($buffered_logs);
    if ($msg = static::fastMultiStore($assembled_logs)) {
      trigger_error($msg, E_USER_WARNING);

      return;
    }

    // Remove the useless temporary buffer
    unlink($tmpbuffer);

    $aggregate_lifetime = CAppUI::conf("aggregate_lifetime");
    if (!$aggregate_lifetime) {
      CApp::log("Could not aggregate logs, no buffer set", $class, CLogger::LEVEL_DEBUG);

      return;
    }

    CApp::doProbably(
      $aggregate_lifetime,
      function () use ($class) {
        $class::aggregate(false, false, true);

        if (!strpos($class, 'Archive')) {
          /** @var CModuleActionLog $archive_class */
          $archive_class = "{$class}Archive";
          $archive_class::aggregate(false, false, true);
        }
      }
    );
  }

  /**
   * Aggregates logs according to date
   *
   * @param bool|true  $dry_run  Dry run, for testing purposes
   * @param bool|false $show_msg Do we have to display ajax message?
   * @param bool|false $report   Do we have to log report?
   *
   * @return void
   * @throws Exception
   *
   */
  static function aggregate($dry_run = true, $show_msg = false, $report = false) {
    $php_chrono = new Chronometer();
    $php_chrono->start();
    $sql_chrono = 0;

    $messages = array();

    $log = new static;
    $ds  = $log->getDS();

    $levels = array(
      'std' => array(
        'current' => 10,
        'next'    => 60,
        'limit'   => CMbDT::date('- 1 MONTH'),
        'format'  => "%Y-%m-%d %H:00:00"
      ),
      'avg' => array(
        'current' => 60,
        'next'    => 1440,
        'limit'   => CMbDT::date('- 1 YEAR'),
        'format'  => "%Y-%m-%d 00:00:00"
      )
    );

    $buffer_lifetime = (CAppUI::conf('access_log_buffer_lifetime')) ?: 100;
    $limit           = $buffer_lifetime * 10;

    foreach ($levels as $_name => $_level) {
      $where = array(
        'period'    => "<= '{$_level['limit']}'",
        'aggregate' => "= '{$_level['current']}'",
      );

      if ($dry_run) {
        $count = $log->countList($where);

        if ($show_msg) {
          $msg = "%d logs to aggregate from level %s older than %s";
          CAppUI::setMsg($msg, UI_MSG_OK, $count, $_level['current'], $_level['limit']);
        }

        continue;
      }

      $logs       = $log->loadList($where, null, $limit);
      $sql_chrono += $ds->chrono->latestStep;

      $count_aggregated = count($logs);

      $log->deleteAll(array_keys($logs));
      $sql_chrono += $ds->chrono->latestStep;

      foreach ($logs as $_log) {
        $_log->period    = CMbDT::format($_log->period, $_level['format']);
        $_log->aggregate = $_level['next'];
      }

      /** @var self $class */
      $class = $log->_class;
      if (!strpos($class, 'Archive')) {
        $class .= 'Archive';
      }

      $logs            = self::assembleLogs($logs);
      $count_assembled = count($logs);

      if ($msg = $class::fastMultiStore($logs, $sql_chrono)) {
        if ($show_msg) {
          CAppUI::setMsg($msg, UI_MSG_ERROR);
        }
        elseif ($report) {
          $messages[] = $msg;
        }

        continue;
      }

      if ($show_msg) {
        $msg = "%d logs inserted to level %s older than %s";
        CAppUI::setMsg($msg, UI_MSG_OK, $count_assembled, $_level['next'], $_level['limit']);
      }
      elseif ($report) {
        // Because of $padding may be dynamic, no locales used here
        $padding    = strlen((string)$limit);
        $text       = "%-21s niveau %4d < %s : %{$padding}d enregistrements supprimés, %{$padding}d agrégés";
        $messages[] = sprintf($text, $log->_class, $_level['current'], $_level['limit'], $count_aggregated, $count_assembled);
      }
    }

    $temps_total = $php_chrono->stop();

    // Print final report
    if ($report) {
      $text         = 'Agrégation des journaux en %01.2f ms (%01.2f ms PHP / %01.2f ms SQL)';
      $query_report = array(
        sprintf($text, $temps_total * 1000, ($temps_total - $sql_chrono) * 1000, $sql_chrono * 1000)
      );

      $query_report = array_merge($query_report, $messages);

      CMbDebug::log(implode("\n", $query_report));
    }
  }
}
