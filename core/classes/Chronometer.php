<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

/**
 * Time tracking utility class
 */
class Chronometer {
  public $total = 0;
  public $step  = 0;
  public $maxStep = 0;
  public $avgStep = 0;
  public $nbSteps = 0;
  public $main = false;
  public $latestStep = 0;

  /** @var self[] */
  public $report = array();
  
  /**
   * Starts the chronometer
   * 
   * @return void
   */
  function start() {
    $this->nbSteps++;
    $this->step = microtime(true);
  }
  
  /**
   * Pauses the chronometer, saving a step
   * 
   * @param string $key The key of the step
   * 
   * @return float|null Step duration in seconds, null on error
   */
  function stop($key = "") {
    if ($this->step === 0) {
      trigger_error("Chrono stopped without starting", E_USER_WARNING);
      return null;
    }
    
    $time = microtime(true);
    $this->step =  $time - $this->step;
    $this->total += $this->step;
    $this->maxStep = max($this->maxStep, $this->step);
    $this->avgStep = $this->total / $this->nbSteps;

    if ($key) {
      if (!array_key_exists($key, $this->report)) {
        $this->report[$key] = new self;
      }
      
      $report =& $this->report[$key];
      $report->nbSteps++;
      $report->step = $this->step;
      $report->total += $report->step;
      $report->maxStep = max($report->maxStep, $report->step);
      $report->avgStep = $report->total/$report->nbSteps;
    }
    
    $this->latestStep = $this->step;
    $this->step = 0;
    return $this->latestStep;
  }

  /**
   * Stop and restart chronometer
   */
  function step($msg) {
    $this->stop($msg);
    $this->start();
  }

  /**
   * Stop, trace latest step and restart a chronometer
   */
  function trace($msg) {
    $step = $this->stop($msg);
    mbTrace(number_format($step*1000, 2), "[Chrono] action '$msg' (ms)");
    $this->start();
  }

  /**
   * Stop, trace latest step and restart a chronometer
   */
  function report() {
    foreach ($this->report as $msg => $_chrono) {
      mbTrace(number_format($_chrono->total   * 1000, 2), "[Chrono] $_chrono->nbSteps '$msg' actions (ms)");
      if ($_chrono->nbSteps > 1) {
        mbTrace(number_format($_chrono->avgStep * 1000, 2), "[Chrono] action '$msg' average (ms)");
      }
    }
  }
}
