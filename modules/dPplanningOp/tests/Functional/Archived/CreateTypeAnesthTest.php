<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateTypeAnesthTest
 *
 * @description Test creation of a new "Type d'anesthésie"
 * @screen      PlanifSejourTypeAnesthPage
 */
class CreateTypeAnesthTest extends SeleniumTestMediboard {
  public $name_typeanesth = "NomTypeAnesth";

  /**
   * Création d'un type d'anesthésie appelé NomTypeAnesth
   */
  public function testCreateTypeAnesthOk() {
    $page = new PlanifSejourTypeAnesthPage($this);
    $msg = $page->createTypeAnesth($this->name_typeanesth);
    $this->assertEquals("Type d'anesthésie créé", $msg);
  }
}