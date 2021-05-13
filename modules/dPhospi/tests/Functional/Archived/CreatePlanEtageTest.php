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
 * @description Test de paramétrage des plans d'étage
 * @screen      PlanEtagePage
 */
class CreatePlanEtageTest extends SeleniumTestMediboard {
  /**
   * Création d'un modèle d'étiquette NomEtiquette pour l'utilisateur CHIR Test avec comme classe Séjour et come texte Texte
   */
  public function testMoveLitOk() {
    $page = new PlanEtagePage($this);
    list($lits_before, $lits_after) = $page->testMoveLit();
    $this->assertEquals($lits_after, $lits_before);
  }
}