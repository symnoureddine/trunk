<?php
/**
 * @package Mediboard\PlanningOp\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateDHETest
 *
 * @description Test creation of a new "DHE" with a protocol
 * @screen      PlanifSejourProtocoleDHEPage, PlanifSejourIntervPage
 */
class CreateDHETest extends SeleniumTestMediboard {
  public $page;

  public $name_protocole = "coucou";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new PlanifSejourProtocoleDHEPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//  }

  /**
   * Création d'un protocole de DHE nommé "coucou" d'une durée de 3 jours avec un temps opération d'une heure
   */
  public function testCreateDHEOk() {
    $page = new PlanifSejourProtocoleDHEPage($this);
    $msg = $page->createProtocoleDHE($this->name_protocole);
    $this->assertEquals("Protocole créé", $msg);
  }

  /**
   * Recherche de l'existance d'un protocole nommé $name_protocole pour le praticien CHIR TEST
   */
  public function testSearchProtocole() {
    $page = new PlanifSejourProtocoleDHEPage($this);
    $page->createProtocoleDHE($this->name_protocole);
    $msg = $page->searchProtocole($this->name_protocole);
    $this->assertEquals($this->name_protocole, $msg);
  }

  /**
   * Essai d'aplication de protocole
   */
  public function testapplyProtocole() {
    $page = new PlanifSejourProtocoleDHEPage($this);
    $page->createProtocoleDHE($this->name_protocole);
    $page->switchTab('vw_edit_planning');
    $page = new PlanifSejourIntervPage($this, false);
    $msg = $page->applyProtocole($this->name_protocole);

    $this->assertEquals($this->name_protocole, $msg["name_protocole"]);
    $this->assertEquals(3       , $msg["duree"]);
    $this->assertEquals("01:00" , $msg["time_op"]);
    $this->assertEquals("comp"  , $msg["type_sejour"]);
  }

  /**
   * Teste la création d'une intervention hors plage avec application de codage CCAM
   */
  public function testCreateOperationHorsPlage() {
    $page = new PlanifSejourIntervPage($this);
    $message = $page->createOperationHorsPlage('CHIR Test', 'PATIENTLASTNAME', 'NFEP002');

    $this->assertContains('Intervention créée', $message);
    $this->assertContains('NFEP002', $page->getCodedCCAMAct('NFEP002'));
  }

  /**
   * Test le changement de plage d'une DHE (ne doit pas vider la spécialité de la nouvelle plage
   *
   * @pref mode_dhe 0
   */
  public function testChangePlageDHE() {
    $this->importObject("dPbloc/tests/Functional/data/bloc.xml");
    $this->importObject("dPbloc/tests/Functional/data/plageop1.xml");
    $this->importObject("dPbloc/tests/Functional/data/plageop2.xml");

    $page = new PlanifSejourProtocoleDHEPage($this);
    $page->createProtocoleDHE($this->name_protocole);

    $page = new PlanifSejourIntervPage($this);
    $page->switchTab("vw_edit_planning");
    $message = $page->changePlageDHE($this->name_protocole, "CHIR Test", "PATIENTLASTNAME");

    $this->assertContains("Intervention modifiée", $message);

    $page->switchModule("dPbloc");
    $page->switchTab("vw_edit_planning");

    $specs_plage = $page->getSpecsPlages();

    $this->assertEquals(array(0 => "OX", 1 => "OX"), $specs_plage);
  }
}