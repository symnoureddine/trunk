<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateModeleTest
 *
 * @description Test creation of a document's modele
 * @screen      ModelesPage
 */
class CreateModeleEtiquetteTest extends SeleniumTestMediboard {
  public $chir_name = "CHIR Test";

  /**
   * Cr�ation d'un mod�le d'�tiquette NomEtiquette pour l'utilisateur CHIR Test avec comme classe S�jour et come texte Texte
   */
  public function testCreateModeleOk() {
    $page = new ModeleEtiquettePage($this);
    $page->createModeleEtiquette();

    $this->assertEquals("Mod�le d'�tiquette cr��", $page->getSystemMessage());
  }

}