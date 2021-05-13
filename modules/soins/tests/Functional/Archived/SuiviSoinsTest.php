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
   * Ajout d'une transmission médicale dans le suivi de soins
   */
  public function testAddTransmission() {
    $page = $this->sejour_page;

    $result = $page->testAddTransmission();

    $this->assertEquals("Donnee", $result["data"]);
  }

  /**
   * Ajout d'une transmission de diététique et vérification de son apparition dans volet correspondant
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
   * Ajout d'une tâche dans le suivi de soins
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
   * Ajout d'une ligne de médicament dans la prescription
   */
  public function testCreateLineMed() {
    $page = $this->sejour_page;

    $product_libelle = "EFFERALGAN 1000 mg";

    $med = $page->testCreateLineMed($product_libelle);

    $this->assertContains($product_libelle, $med);
  }

  /**
   * Ajout d'une ligne d'élément dans la prescription
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
   * Ajout d'une allergie et vérification de l'icône dans le bandeau patient
   */
  public function testCreateAllergie() {
    $page = $this->sejour_page;

    $flag_allergie = $page->testCreateAllergie();

    $this->assertTrue($flag_allergie);
  }

  /**
   * Ajout d'un antécédent et vérification de l'icône dans le bandeau patient
   */
  public function testCreateAntecedent() {
    $page = $this->sejour_page;

    $flag_atcd = $page->testCreateAntecedent();

    $this->assertTrue($flag_atcd);
  }

  /**
   * Ajout d'un antécédent contenant un code CIM10,
   * et vérifie que le code CIM10 est bien ajouté dans les éléments significatifs du séjour
   *
   * @pref AUTOADDSIGN 1
   */
  public function testCreateAntecedentCIM10() {
    $page = $this->sejour_page;

    $page->testCreateAntecedent('A01');

    $this->assertContains('Antécédent créé', $page->getSystemMessage());

    $this->assertContains('A01', $page->getDiagnosticSignificatif());
  }

  /**
   * Teste la suppression d'un antecédent,
   * et vérifie qu'il est également supprimé des éléments significatifs du séjour
   *
   * @pref AUTOADDSIGN 1
   */
  public function testDeleteAntecedent() {
    $page = $this->sejour_page;

    $page->testCreateAntecedent();

    $page->deleteAntecedent();

    $this->assertContains('Elements supprimés', $page->getSystemMessage());

    $this->assertContains('Aucun Antécédent', $page->getAntecedentSignificatif());

  }

  /**
   * Vérification de l'icône d'absence d'allergie dans le bandeau patient
   */
  public function testAjoutNoAllergie() {
    $page = $this->sejour_page;

    $flag_no_allergie = $page->testAjoutNoAllergie();

    $this->assertTrue($flag_no_allergie);
  }

  /**
   * Vérification de la non prescription d'un médicament en tant qu'infirmière
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAddMedInfirmiere() {
    $page = $this->sejour_page;

    $testAddMedInfirmiere = $page->testAddMedInfirmiere();

    $this->assertFalse($testAddMedInfirmiere);
  }

  /**
   * Vérification de la non prescription d'un médicament en tant que sage femme
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAddMedSageFemme() {
    $page = $this->sejour_page;

    $testAddMedSageFemme = $page->testAddMedSageFemme();

    $this->assertFalse($testAddMedSageFemme);
  }

  /**
   * Vérification de l'administration d'une ligne de médicament
   *
   * @config [CConfiguration] mpm general role_propre 1
   */
  public function testAdmLineMed() {
    $page = $this->sejour_page;

    $quantites = $page->testAdmLineMed("EFFERALGAN 1 g");

    $this->assertEquals($quantites["qte_adm"], $quantites["qte_prescrite"]);
  }

  /**
   * Vérification de la création d'une observation médicale en tant que praticien
   */
  public function testAddObservation() {
    $page = $this->sejour_page;

    $obs = $page->testAddObservation();

    $this->assertEquals("Observation médicale créée", $obs);
  }

  /**
   * Vérification de l'autorisation de sortie depuis la synthèse du dossier de soins
   */
  public function testAutorisationSortie() {
    $page = $this->sejour_page;

    $sortie_autorisee = $page->testAutorisationSortie();

    $this->assertTrue($sortie_autorisee);
  }

  /**
   * Vérification de la présence de la puce d'alerte lors de la prescription d'un médicament
   */
  public function testAlerteAllergie() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteAllergie("Paracétamol", "EFFERALGAN 1 g");

    $this->assertTrue($puce);
  }

  /**
   * Vérification de l'alerte de redondance lors de la prescription de deux médicaments
   */
  public function testAlerteRedondance() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteRedondance("EFFERALGAN 1 g", "PARACETAMOL");

    $this->assertTrue($puce);
  }

  /**
   * Vérification de l'alerte d'IPC lors de la prescription de deux médicaments
   */
  public function testAlerteIPC() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteIPC("CERUBIDINE", "FLUDARA");

    $this->assertTrue($puce);
  }

  /**
   * Vérification de l'alerte d'interaction lors de la prescription de deux médicaments
   */
  public function testAlerteInteraction() {
    $page = $this->sejour_page;

    $puce = $page->testAlerteInteraction("RENITEC", "CHLORURE DE POTASSIUM");

    $this->assertTrue($puce);
  }

  /**
   * Vérification de la création de naissance
   */
  public function testCreateNaissance() {
    $page = $this->sejour_page;

    $identite = $page->testCreateNaissance();

    $this->assertNotNull($identite);
  }

  /**
   * Vérification de la création d'allaitement
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
   * Création d'un RDV externe et vérification de sa présence dans le planning de séjour (volet synthèse)
   */
  public function testCreateExternalRDVOk() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");
    $page = $this->sejour_page;
    $libelle = "Coiffeur";
    $description = "RDV chez le coiffeur mais il peut avoir du retard";
    $duree = 90;

    $number = $page->createExternalRDV($libelle, $description, $duree);
    $this->assertEquals("Rendez-vous externe créé", $page->getSystemMessage());
    $this->assertEquals(1, $number);

    $this->assertContains($libelle, $page->checkExternalRDVInPlanning());
  }

  /**
   * Création d'une perfusion à quantité indéterminée, planification dans
   * le plan de soins et vérification de la présence de la planification
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
   * Création d'un correspondant et vérification de son affichage dans la synthèse
   */
  public function testAddCorrespondant() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");

    $page = $this->sejour_page;

    $result = $page->testAddCorrespondant();

    $this->assertEquals("CORRESPONDANT Prenom", $result);
  }

  /**
   * Création d'un correspondant médical et vérification de son affichage dans la synthèse
   */
  public function testAddCorrespondantMedical() {
    $this->importObject("dPpatients/tests/Functional/data/patient_sejour.xml");

    $page = $this->sejour_page;

    $result = $page->testAddCorrepondantMedical();

    $this->assertEquals("CORRESPONDANT", $result);
  }
}
