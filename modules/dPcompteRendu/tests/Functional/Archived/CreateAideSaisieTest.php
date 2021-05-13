<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateModeleTest
 *
 * @description Test creation of a helper
 * @screen      AideSaisiePage
 */
class CreateAideSaisieTest extends SeleniumTestMediboard {
  public $chir_name = "CHIR Test";

  /**
   * Création d'une aide à la saisie appelée NomAideSaisie pour l'utilisateur CHIR Test avec comme texte Contenu aide saisie
   */
  public function testCreateAideSaisieOk() {
    $page = new AideSaisiePage($this);
    $page->createAideSaisie($this->chir_name);

    $this->assertEquals("Aide à la saisie créée", $page->getSystemMessage());
  }

}