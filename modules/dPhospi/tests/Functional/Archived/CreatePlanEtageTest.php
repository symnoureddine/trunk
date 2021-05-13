<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreatePlanEtageTest
 *
 * @description Test de param�trage des plans d'�tage
 * @screen      PlanEtagePage
 */
class CreatePlanEtageTest extends SeleniumTestMediboard {
  /**
   * Cr�ation d'un mod�le d'�tiquette NomEtiquette pour l'utilisateur CHIR Test avec comme classe S�jour et come texte Texte
   */
  public function testMoveLitOk() {
    $page = new PlanEtagePage($this);
    list($lits_before, $lits_after) = $page->testMoveLit();
    $this->assertEquals($lits_after, $lits_before);
  }
}