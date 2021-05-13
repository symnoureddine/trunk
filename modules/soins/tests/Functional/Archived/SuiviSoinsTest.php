<?php 
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * SuiviSoins Test
 *
 * @description Several tests in the suivi
 * @screen      SejourPage
 */
class SuiviSoinsTest extends SeleniumTestMediboard {
  /** @var SejourPage $sejour_page */
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
   * Ajout d'une transmission m�dicale dans le suivi de soins
   */
  public function testAddTransmission() {
    $page = $this->sejour_page;

    $result = $page->testAddTransmission();

    $this->assertEquals("Donnee", $result["data"]);
  }

  /**
   * Ajout d'une transmission de di�t�tique et v�rification de son apparition dans volet correspondant
   *
   * @config [CConfiguration] soins Other see_volet_diet 1
   */
  public function testAddTransmissionDiet() {
    $page = $this->sejour_page;
    $page->testAddTransmission("Donnee", null, null, true);

    $result = $page->searchInOngletDiet();
    $this->assertEquals("Donnee", $result["data"]);
  }

  /**
   * Ajout d'une t�che dans le suivi de soins
   */
  public function testAddSejourTask() {
    $page = $this->sejour_page;

    $result = $page->testAddSejourTask();

    $this->assertEquals("Description", $result["description"]);
  }

  /**
   * Ajout d'un objectif de soins dans le suivi de soins
   */
  public function testAddObjectifSoin() {
    $page = $this->sejour_page;

    $result = $page->testAddObjectifSoins();

    $this->assertEquals("Libelle", $result);
  }

  /**
   * Ajout d'une ligne de m�dicament dans la prescription
   */
  public function testCreateLineMed() {
    $page = $this->sejour_page;

    $product_libelle = "EFFERALGAN 1000 mg";

    $med = $page->testCreateLineMed($product_libelle);

    $this->assertContains($product_libelle, $med);
  }

  /**
   * Ajout d'une ligne d'�l�ment dans la prescription
   */
  public function testCreateLineElt() {
    $this->importObject("dPprescription/tests/Functional/data/element_category_test.xml");

    $page = $this->sejour_page;

    $chapitre = "biologie";
    $element = "Element";

    $elt = $page->testCreateLineElt($chapitre, $element);

    $this->assertContains($element, $elt);
  }

  /**
   * Ajout d'une allergie et v�rification de l'ic�ne dans le bandeau patient
   */
  public function testCreateAllergie() {
    $page = $this->sejour_page;

    $flag_allergie = $page->testCreateAllergie();

    $this->assertTrue($flag_allergie);
  }

  /**
   * Ajout d'un ant�c�dent et v�rification de l'ic�ne dans le bandeau patient
   */
  public function testCreateAntecedent() {
    $page = $this->sejour_page;

    $flag_atcd = $page->testCreateAntecedent();

    $this->assertTrue($flag_atcd);
  }

  /**
   * Ajout d'un ant�c�dent contenant un code CIM10,
   * et v�rifie que le code CIM10 est bien ajout� dans les �l�ments significatifs du s�jour
   *
   * @pref AUTOADDSIGN 1
   */
  public function testCreateAntecedentCIM10() {
    $page = $this->sejour_page;

    $page->testCreateAntecedent('A01');

    $this->assertContains('Ant�c�dent cr��', $page->getSystemMessage());

    $this->assertContains('A01', $page->getDiagnosticSignificatif());
  }

  /**
   * Teste la suppression d'un antec�dent,
   * et v�rifie qu'il est �galement supprim� des �l�ments significatifs du s�jour
   *
   * @pref AUTOADDSIGN 1
   */
  public function testDeleteAntecedent() {
    $page = $this->sejour_page;

    $page->testCreateAntecedent();

    $page->deleteAntecedent();

    $this->assertContains('Elements supprim�s', $page->getSystemMessage());

    $this->assertContains('Aucun Ant�c�dent', $page->getAntecedentSignificatif());

  }

  /**
   * V�rification de l'ic�ne d'absence d'allergie dans le bandeau patient
   */
  public function testAjoutNoAllergie() {
    $page = $this->sejour_page;

    $flag_no_allergie = $page->testAjoutNoAllergie();

    $this->assertTrue($flag_no_allergie);
  }

  /**
   * V�rification de la non prescription d'un m�dicament en tant qu'infirmi�re
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAddMedInfirmiere() {
    $page = $this->sejour_page;

    $testAddMedInfirmiere = $page->testAddMedInfirmiere();

    $this->assertFalse($testAddMedInfirmiere);
  }

  /**
   * V�rification de la non prescription d'un m�dicament en tant que sage femme
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAddMedSageFemme() {
    $page = $this->sejour_page;

    $testAddMedSageFemme = $page->testAddMedSageFemme();

    $this->assertFalse($testAddMedSageFemme);
  }

  /**
   * V�rification de l'administration d'une ligne de m�dicament
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAdmLineMed() {
    $page = $this->sejour_page;

    $quantites = $page->testAdmLineMed("EFFERALGAN 1 g");

    $this->assertEquals($quantites["qte_adm"], $quantites["qte_prescrite"]);
  }

  /**
   * V�rification de la cr�ation d'une observation m�dicale en tant que praticien
   */
  public function testAddObservation() {
    $page = $this->sejour_page;

    $obs = $page->testAddObservation();

    $this->assertEquals("Observation m�dicale cr��e", $obs);
  }

  /**
   * V�rification de l'autorisation de sortie depuis la synth�se du dossier de soins
   */
  public function testAutorisationSortie() {
    $page = $this->sejour_page;

    $sortie_autorisee = $page->testAutorisationSortie();

    $this->assertTrue($sortie_autorisee);
  }

  /**
   * V�rification de la pr�sence de la puce d'alerte lors de la prescription d'un m�dicament
   */
  public function testAlerteAllergie() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteAllergie("Parac�tamol", "EFFERALGAN 1 g");

    $this->assertTrue($puce);
  }

  /**
   * V�rification de l'alerte de redondance lors de la prescription de deux m�dicaments
   */
  public function testAlerteRedondance() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteRedondance("EFFERALGAN 1 g", "PARACETAMOL");

    $this->assertTrue($puce);
  }

  /**
   * V�rification de l'alerte d'IPC lors de la prescription de deux m�dicaments
   */
  public function testAlerteIPC() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteIPC("CERUBIDINE", "FLUDARA");

    $this->assertTrue($puce);
  }

  /**
   * V�rification de l'alerte d'interaction lors de la prescription de deux m�dicaments
   */
  public function testAlerteInteraction() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteInteraction("RENITEC", "CHLORURE DE POTASSIUM");

    $this->assertTrue($puce);
  }

  /**
   * V�rification de la cr�ation de naissance
   */
  public function testCreateNaissance() {
    $page = $this->sejour_page;

    $identite = $page->testCreateNaissance();

    $this->assertNotNull($identite);
  }

  /**
   * V�rification de la cr�ation d'allaitement
   */
  public function testCreateAllaitement() {
    $page = $this->sejour_page;

    $view_allaitement = $page->testCreateAllaitement();

    $this->assertNotNull($view_allaitement);
  }

  public function testPresenceGrossesseTab() {
    $page = $this->sejour_page;

    $this->importObject("maternite/tests/Functional/data/sejour_grossesse.xml");

    $button_consultation = $page->testPresenceGrossesseTab();

    $this->assertNotNull($button_consultation);
  }

  /**
   * Cr�ation d'un RDV externe et v�rification de sa pr�sence dans le planning de s�jour (volet synth�se)
   */
  public function testCreateExternalRDVOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
    $page = $this->sejour_page;
    $libelle = "Coiffeur";
    $description = "RDV chez le coiffeur mais il peut avoir du retard";
    $duree = 90;

    $number = $page->createExternalRDV($libelle, $description, $duree);
    $this->assertEquals("Rendez-vous externe cr��", $page->getSystemMessage());
    $this->assertEquals(1, $number);

    $this->assertContains($libelle, $page->checkExternalRDVInPlanning());
  }

  /**
   * Cr�ation d'une perfusion � quantit� ind�termin�e, planification dans
   * le plan de soins et v�rification de la pr�sence de la planification
   *
   * @config [CConfiguration] dPprescription CPrescription define_quantity 1
   * @config [CConfiguration] planSoins general unite_prescription_plan_soins 0
   */
  public function testPlanifPerfQteIndeterminee() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");

    $page = $this->sejour_page;

    $result = $page->testPlanifPerfQteIndeterminee();

    $this->assertEquals("0 /40", $result);
  }

  /**
   * Cr�ation d'un correspondant et v�rification de son affichage dans la synth�se
   */
  public function testAddCorrespondant() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");

    $page = $this->sejour_page;

    $result = $page->testAddCorrespondant();

    $this->assertEquals("CORRESPONDANT Prenom", $result);
  }

  /**
   * Cr�ation d'un correspondant m�dical et v�rification de son affichage dans la synth�se
   */
  public function testAddCorrespondantMedical() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");

    $page = $this->sejour_page;

    $result = $page->testAddCorrepondantMedical();

    $this->assertEquals("CORRESPONDANT", $result);
  }
}
