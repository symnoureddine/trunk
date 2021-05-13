<?php
/**
 * @package Mediboard\Soins\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CheckQteAdmMixTest
 *
 * @description Test administrated quantity and prescribed quantity
 * @screen      SejourPage
 */
class CheckQteAdmMixTest extends SeleniumTestMediboard {
  /** @var SejourPage $page */
  public $sejour_page;
  public $chir_name = "CHIR Test";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->sejour_page = new SejourPage($this);
//    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
//    $this->importObject("dPplanningOp/tests/Functional/data/sejour_test.xml");
//  }

  /**
   * Vérifie la quantité de perfusion administrée dans le plan de soins avec une prescription monoproduit.
   *
   * @config [CConfiguration] planSoins general unite_prescription_plan_soins 1
   */
  public function testCheckQteAdmMixOk() {
    $page = $this->sejour_page;

    $quantites = $page->checkQteAdmiMix("PERFALGAN 10 mg/ml", 10);

    $this->assertEquals($quantites["qte_adm"], $quantites["qte_prescrite"]);
  }

  /**
   * Vérifie le débit d'une perfusion exprimé d'après le poids du patient
   */
  public function testDebitKgOk() {
    $this->importObject("dPprescription/tests/Functional/data/protocole_test.xml");

    $page = $this->sejour_page;

    $debit = $page->checkDebitKg();

    $this->assertEquals(1.5, $debit);
  }

  /**
   * Vérifie la création et l'administration d'une inscription de médicament
   *
   * @config [CConfiguration] dPprescription CPrescription show_inscription 1
   * @config [CConfiguration] planSoins general unite_prescription_plan_soins 1
   */
  public function testCreateInscriptionMedOk() {
    $page = $this->sejour_page;

    $qte_adm = $page->checkCreateInscriptionMed($this->chir_name, "efferalgan", 1);

    $this->assertEquals(1, $qte_adm);
  }

  /**
   * Vérifie la création et l'administration d'une inscription d'élément
   *
   * @config [CConfiguration] dPprescription CPrescription show_inscription 1
   */
  public function testCreateInscriptionEltOk() {
    $this->importObject("dPprescription/tests/Functional/data/element_category_test.xml");

    $page = $this->sejour_page;

    $qte_adm = $page->checkCreateInscriptionElt($this->chir_name, "Element", 1);

    $this->assertEquals(1, $qte_adm);
  }

  public function testPresenceTrashButtonInscriptionMed() {
    $page = $this->sejour_page;

    $page->checkCreateInscriptionMed($this->chir_name, "efferalgan", 1);

    $button_trash = $page->testPresenceTrashButtonInscriptionMed();

    $this->assertNotNull($button_trash);
  }
}