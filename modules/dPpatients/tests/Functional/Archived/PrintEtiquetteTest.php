<?php

use Ox\Tests\SeleniumTestMediboard;

/**
 * PrintEtiquetteTest
 *
 * @description Test impression d'étiquette
 * @screen      DossierPatientPage
 */
class PrintEtiquetteTest extends SeleniumTestMediboard {

  /** @var DossierPatientPage $page */
  public $page = null;

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//    $this->page = new DossierPatientPage($this);
//    $this->importObject("dPpatients/tests/Functional/data/patient_consult_test.xml");
//    $this->importObject("dPpatients/tests/Functional/data/modele_etiquette.xml");
//  }

  /**
   * Génère une planche d'étiquette
   */
  public function testPrintEtiquettesOK() {
    $page = $this->page;
    $this->assertTrue($page->testPrintEtiquettes());
  }
}