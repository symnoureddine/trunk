<?php
/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * MainCouranteTest
 *
 * @description Test creation RPU
 * @screen      RPUPage
 */

class MainCouranteTest extends SeleniumTestMediboard {
  public $default_protocole = array(
    "mode_entree"    => "6",
    "transport"      => "perso",
    "responsable_id" => "CHIR Test"
  );

  public $default_protocole2 = array(
    "mode_entree"    => "7",
    "transport"      => "perso_taxi",
    "responsable_id" => "CHIR Test"
  );

  /**
   * Test d'application de protocole de RPU
   */
  public function testApplyProtocoleRPU() {
    $page = new RPUPage($this);
    $this->importObject("dPurgences/tests/Functional/data/protocole_rpu.xml");
    $result = $page->testApplyProtocoleRPU();

    $this->assertEquals($this->default_protocole, $result);
  }

  /**
   * Test d'application par choix de protocole de RPU
   */
  public function testChooseProtocoleRPU() {
    $page = new RPUPage($this);

    $this->importObject("dPurgences/tests/Functional/data/protocole_rpu.xml");
    $this->importObject("dPurgences/tests/Functional/data/protocole_rpu2.xml");
    $result = $page->testChooseProtocoleRPU();

    $this->assertEquals("Protocole1", $result);
    //$this->assertEquals($this->default_protocole2, $result);
  }
}