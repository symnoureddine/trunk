<?php
/**
 * @package Mediboard\Urgences\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateRPUTest
 *
 * @description Test creation RPU
 * @screen      RPUPage
 */
class CreateRPUTest extends SeleniumTestMediboard {
  public $mode_entree = "Domicile";
  public $provenance  = "PEC aux urgences (non org.)";
  public $transport   = "Moyens personnels";
  public $patientLastname = "PatientLastname";

  /**
   * Création d'un RPU
   *
   * @config ref_pays 1
   */
  public function testCreateRPUOk() {
    $page = new RPUPage($this);
    $this->importObject("mediusers/tests/Functional/data/mediuser_infirmiere.xml");
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $page->createRPU($this->patientLastname, $this->mode_entree, $this->provenance, $this->transport);
    $this->assertContains("RPU créé", $page->getSystemMessage());
  }

  /**
   * Crée la prise en charge d'urgence
   *
   * @config ref_pays 1
   */
  public function testPecRPU() {
    $page = new RPUPage($this);
    $this->testCreateRPUOk();
    $page->pecRPU();
    $this->assertContains("Consultation créée", $page->getSystemMessage());
  }

  /**
   * Vérifie la possibilité de reconvocation
   */
  public function testPossibleReconvocation() {
    $page = new RPUPage($this);
    $this->testPecRPU();

    $this->assertNotNull($page->testPossibleReconvocation());
  }
}
