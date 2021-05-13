<?php
/**
 * @package Mediboard\Hospi\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * FunctionsTest
 *
 * @description Tests de paramétrages des fonctions
 * @screen      FunctionsPage
 */
class FunctionsTest extends SeleniumTestMediboard {
  public $name_fct = "Fonction1";
  public $type_fct = "cabinet";
  public $color_fct = "0b5394";
  public $user_secondary = "CHIR Test";

  /**
   * Création d'une fonction
   */
  public function testaddFunctionOk() {
    $page = new FunctionsPage($this);
    $page->testaddFunction($this->name_fct, $this->type_fct, $this->color_fct);
    $msg = str_replace('^', '', $page->getSystemMessage());
    $this->assertEquals("Fonction créée", $msg);
  }

  /**
   * Test d'ajout d'un utilisateur à une fonction secondaire
   */
  public function testAddUserSecondaryFunctionOk() {
    $page = new FunctionsPage($this);
    $page->testaddFunction($this->name_fct, $this->type_fct, $this->color_fct);
    $page->testAddUserSecondaryFunction($this->name_fct, $this->user_secondary);
    $msg = str_replace('^', '', $page->getSystemMessage());
    $this->assertEquals("Fonction secondaire créée", $msg);
  }
}