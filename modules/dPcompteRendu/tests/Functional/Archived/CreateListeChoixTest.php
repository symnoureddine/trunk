<?php
/**
 * @package Mediboard\CompteRendu\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateListeChoixTest
 *
 * @description Test creation of a helper
 * @screen      AideSaisiePage
 */
class CreateListeChoixTest extends SeleniumTestMediboard {
  public $chir_name = "CHIR Test";

  /**
   * Création d'une liste de choix appelée NomListeChoix pour l'utilisateur CHIR Test
   */
  public function testCreateListeChoixOk() {
    $page = new ListeChoixPage($this);
    $page->createListeChoix($this->chir_name);

    $this->assertEquals("Liste créée", $page->getSystemMessage());
  }

}