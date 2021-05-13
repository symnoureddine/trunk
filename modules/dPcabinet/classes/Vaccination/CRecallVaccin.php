<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Cabinet\Vaccination;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;

/**
 * Vaccin recall object
 */
class CRecallVaccin implements IShortNameAutoloadable {
  public $age_recall; // Advised injection age
  public $age_max;
  public $repeat;
  public $colspan = false;
  public $empty = false;
  public $mandatory = false;

  public $repeat_recall;

  public $recall_age; // For which advised injection age (forms)


  /**
   * CRecallVaccin constructor.
   *
   * @param int  $age_recall
   * @param int  $age_max
   * @param int  $repeat
   * @param int  $colspan
   * @param bool $empty
   * @param bool $mandatory
   */
  public function __construct($age_recall, $age_max = null, $repeat = null, $colspan = 1, $empty = false, $mandatory = false) {
    $this->age_recall = $age_recall;
    $this->age_max    = $age_max;
    $this->repeat     = $repeat;
    $this->colspan    = ($colspan) ? $colspan : 1;
    $this->empty      = $empty;
    $this->mandatory  = $mandatory;
  }

  /**
   * Makes a recall object using the age
   *
   * @param int $age
   *
   * @return CRecallVaccin
   */
  public static function makeRecallVaccine($age) {
    return new CRecallVaccin($age);
  }

  /**
   * Gets the recall age and sets it in the object: number of months
   *
   * @return int
   */
  public function getRecallAge() {
    return (int)$this->age_recall;
  }

  /**
   * Gets the date as string
   *
   * @return String $string - the date
   */
  public function getStringDates() {
    $suffixe = ($this->age_recall < 24) ? CAppUI::tr("month") : CAppUI::tr("years");

    $string_date = ($this->age_recall < 24) ? $this->age_recall : $this->age_recall / 12;
    $age_max     = (!$this->age_max && $this->age_max < 24) ? $this->age_max : $this->age_max / 12;
    $string_date .= ($this->age_max) ? " - " . $age_max : "";
    $string_date .= " " . $suffixe;
    $string_date .= ($this->repeat > 0) ? " " . CAppUI::tr("and") . " +" : "";

    return $string_date;
  }
}
