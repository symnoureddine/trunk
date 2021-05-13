<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateParametrageTest
 *
 * @screen PlanifSejourParametragePage
 */
class CreateParametrageTest extends SeleniumTestMediboard {
  public $name_mode_traitement = "mode1";

  /**
   * Création d'un mode de traitement
   */
  public function testCreateModeTraitement() {
    $page = new PlanifSejourParametragePage($this);
    $msg = $page->createModeTraitement($this->name_mode_traitement);
    $this->assertEquals($this->name_mode_traitement, $msg);
  }
}