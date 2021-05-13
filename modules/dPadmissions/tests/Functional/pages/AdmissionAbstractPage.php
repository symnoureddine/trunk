<?php
/**
 * @package Mediboard\Admissions\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Facebook\WebDriver\WebDriverBy;
use Ox\Tests\HomePage;

/**
 * Admissions page representation
 */
class AdmissionAbstractPage extends HomePage {

  protected $module_name = "dPadmissions";

  /**
   * Get the current date
   *
   * @param string $tabId  Identifiant du tableau à attendre avant d'accéder à la date
   * @param string $dateId Identifiant de l'input de la date
   *
   * @return string
   */
  public function getCurrentDate($tabId = "admissions", $dateId = "changeDateAdmissions_date") {
    $driver = $this->driver;
    if ($tabId) {
      $driver->byId($tabId);
    }
    return $currentDate = $driver->executeScript("return \$V('$dateId');");
  }

  /**
   * Get all the lines of admissions table
   *
   * @param string $tabId Identifiant du tableau de la liste des jours
   *
   * @return array
   */
  public function getMonthCalendar($tabId = "allAdmissions") {
    $driver = $this->driver;
    return $arrayDays = $driver->findElements(WebDriverBy::cssSelector("#$tabId > table > tbody > tr"));
  }

  /**
   * Get the numbers of days within the admissions table
   *
   * @param string $tabId        Identifiant du tableau de la liste des jours
   * @param int    $extraColumns Nombre de colonnes supplémentaires à ne pas prendre en compte
   *
   * @return int
   */
  public function countMonthDays($tabId = "allAdmissions", $extraColumns = 1) {
    $arrayDays = $this->getMonthCalendar($tabId);
    return (count($arrayDays) - $extraColumns);
  }

  /**
   * Change month
   *
   * @param string $idTable Identifiant du tableau de la liste des jours
   * @param string $idList  Identifiant du tableau de la liste des patients
   *
   * @return void
   */
  public function changeMonth($idTable = "allAdmissions", $idList = "listAdmissions") {
    $driver = $this->driver;
    $driver->byCss("#$idTable thead a:nth-child(2)")->click();
    $driver->waitForAjax($idTable);
    $driver->waitForAjax($idList);
  }

}