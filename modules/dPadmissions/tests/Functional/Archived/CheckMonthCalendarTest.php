<?php
/**
 * @package Mediboard\Admissions\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CheckMonthCalendarTest
 *
 * @description Test if admission calendar is ok
 * @screen      AdmissionPage
 */
class CheckMonthCalendarTest extends SeleniumTestMediboard {

  /**
   * Calcul du nombre de jours affichés dans le listing de gauche et comparaison avec le nombre du jours du mois actuel.
   * Test du mois courant au 12 mois suivants
   */
  public function testAdmissionsDaysCountOk() {
    $admissionPage = new AdmissionsPage($this);
    for ($i = 1; $i <= 12; $i++) {
      $currentDate = $admissionPage->getCurrentDate("admissions", "changeDateAdmissions_date");
      $nbDays = date('t', strtotime($currentDate));
      $daysNumber = $admissionPage->countMonthDays("allAdmissions");
      $this->assertEquals($nbDays, $daysNumber);
      $admissionPage->changeMonth("allAdmissions", "listAdmissions");
    }
  }

  /**
   * Calcul du nombre de jours affichés dans le listing de gauche et comparaison avec le nombre du jours du mois actuel.
   * Test du mois courant au 12 mois suivants
   */
  public function testSortiesDaysCountOk() {
    $admissionPage = new SortiesPage($this);
    for ($i = 1; $i <= 12; $i++) {
      $currentDate = $admissionPage->getCurrentDate("sortie", "changeDateSorties_date");
      $nbDays = date('t', strtotime($currentDate));
      $daysNumber = $admissionPage->countMonthDays("allSorties");
      $this->assertEquals($nbDays, $daysNumber);
      $admissionPage->changeMonth("allSorties", "listSorties");
    }
  }
}