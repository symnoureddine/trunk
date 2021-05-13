<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Ssr\Test;

use Ox\Core\CMbDT;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Personnel\CPlageConge;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Ssr\CBilanSSR;
use Ox\Mediboard\Ssr\CPlateauTechnique;
use Ox\Mediboard\Ssr\CTechnicien;
use Ox\Mediboard\Ssr\Generators\CTechnicienGenerator;
use Ox\Tests\UnitTestMediboard;

class CTechnicienTest extends UnitTestMediboard {

  public function test__construct() {
    $technicien = new CTechnicien();
    $this->assertInstanceOf(CTechnicien::class, $technicien);
  }

  public function testLoadRefPlateau() {
    $technicien = new CTechnicien();
    $this->assertInstanceOf(CPlateauTechnique::class, $technicien->loadRefPlateau());
  }

  public function testLoadRefKine() {
    $technicien = new CTechnicien();
    $this->assertInstanceOf(CMediusers::class, $technicien->loadRefKine());
  }

  public function testLoadRefCongeDate() {
    $technicien = new CTechnicien();
    $this->assertInstanceOf(CPlageConge::class, $technicien->loadRefCongeDate(CMbDT::date()));
  }

  public function testLoadRefsSejours() {
    $technicien = new CTechnicien();
    $this->assertContainsOnlyInstancesOf(CSejour::class, $technicien->loadRefsSejours(CMbDT::date()));
  }

  /**
   * Test sur la génération de la view du technicien en fonction de son lien de kiné et de plateau
   *
   * @throws \Ox\Tests\TestsException
   */
  public function testUpdateView() {
    $kine = $this->getRandomObjects(CMediusers::class);

    $plateau = $this->getRandomObjects(CPlateauTechnique::class);

    $technicien = new CTechnicien();
    $technicien->updateView();
    $this->assertEquals("", $technicien->_view);

    $technicien->_ref_kine = $kine;
    $technicien->updateView();
    $this->assertEquals($kine->_view, $technicien->_view);

    $technicien->_ref_plateau = $plateau;
    $technicien->updateView();
    $this->assertEquals("$kine->_view &ndash; $plateau->_view", $technicien->_view);
  }

  /**
   * @config [CConfiguration] dPplanningOp CSejour check_collisions no
   */
//  public function testCountSejoursDate() {
//    $this->markTestSkipped('Wait for correction');
//    $date       = CMbDT::date();
//    $group      = CGroups::loadCurrent();
//    $technicien = (new CTechnicienGenerator())->setForce(true)->generate();
//    $kine       = $technicien->loadRefKine();
//
//    $sejours = $technicien->loadRefsSejours($date);
//    $this->assertCount(0, $sejours);
//
//    $this->createSejourByTechnicien($technicien, $group, $kine);
//    $sejours = $technicien->loadRefsSejours($date);
//    $this->assertContainsOnlyInstancesOf(CSejour::class, $sejours);
//    $this->assertCount(1, $sejours);
//
//    $this->createSejourByTechnicien($technicien, $group, $kine);
//    $sejours = $technicien->loadRefsSejours($date);
//    $this->assertContainsOnlyInstancesOf(CSejour::class, $sejours);
//    $this->assertCount(2, $sejours);
//  }

  private function createSejourByTechnicien($technicien, $group, $kine) {
    $patient = $this->getRandomObjects(CPatient::class);

    $sejour = new CSejour();
    $sejour->patient_id    = $patient->_id;
    $sejour->group_id      = $group->_id;
    $sejour->praticien_id  = $kine->_id;
    $sejour->type          = "ssr";
    $sejour->entree = CMbDT::dateTime();
    $sejour->entree_prevue = CMbDT::dateTime();
    $sejour->sortie = CMbDT::dateTime("+ 5 days");
    $sejour->sortie_prevue = CMbDT::dateTime("+ 5 days");

    if ($msg = $sejour->store()) {
      $this->fail($msg);
    }

    $bilan = new CBilanSSR();
    $bilan->sejour_id = $sejour->_id;
    $bilan->technicien_id = $technicien->_id;

    if ($msg = $bilan->store()) {
      $this->fail($msg);
    }
  }
}
