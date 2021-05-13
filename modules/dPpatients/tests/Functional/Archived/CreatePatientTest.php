<?php
/**
 * @package Mediboard\Patients\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbSecurity;
use Ox\Tests\SeleniumTestMediboard;

/**
 * CreatePatientTest
 *
 * @description Test creation of a patient
 * @screen      DossierPatientPage
 */
class CreatePatientTest extends SeleniumTestMediboard {

  /** @var $dpPage DossierPatientPage */
  public $dpPage = null;

  public $patientFirstname;
  public $patientLastname;
  public $patientGender = "m";
  public $patientBirthDate = "12/12/1999";

  /**
   * @inheritdoc
   */
//  public function setUp() {
//    parent::setUp();
//
//    $this->dpPage = new DossierPatientPage($this);
//  }

  /**
   * Créé un patient avec un nom et un prénom aléatoire
   */
  public function testCreatePatientOk() {
    $page                   = $this->dpPage;
    $this->patientLastname  = CMbSecurity::getRandomString(8);
    $this->patientFirstname = CMbSecurity::getRandomString(8);

    $page->searchPatientByName($this->patientLastname);
    $page->createPatient($this->patientFirstname, $this->patientGender, $this->patientBirthDate);
    $this->assertEquals(strtoupper($this->patientLastname), $page->getPatientName());
  }

  /**
   * Créé un patient anonyme
   */
  public function testCreateAnonymousPatientOk() {
    $page = $this->dpPage;
    $page->searchPatientByFirstName("anonyme");
    $page->createPatient("", $this->patientGender, $this->patientBirthDate, true);
    $this->assertEquals("Anonyme", $page->getPatientFirstname());
  }

  /**
   * Coche le status BMR dans le dossier patient
   */
  public function testStatusBMRBHRe() {
    $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    $page = $this->dpPage;

    $page->searchPatientByName("PATIENTLASTNAME");

    $status = $page->testBMRBHReStatus();

    $this->assertEquals("BMR+", $status);
  }

  /**
   * Rendre un patient anonyme et vérifier que les champs remplis avant anomymisation soit vidés.
   */
  public function testMakeAnonymousPatientAndResetFieldsOk() {
    $page          = $this->dpPage;
    $patient_datas = array(
      "sexe_f"                   => null,
      "naissance"                => "1921-05-23",
      "situation_famille"        => "M",
      "mdv_familiale"            => "C",
      "condition_hebergement"    => "proprietaire",
      "rang_naissance"           => 5,
      "cp_naissance"             => 17000,
      "lieu_naissance"           => "La Rochelle",
      "niveau_etudes"            => "es",
      "activite_pro"             => "a",
      "fatigue_travail_0"        => null,
      "travail_hebdo"            => 35,
      "transport_jour"           => 22,
      "qual_beneficiaire"        => "04",
      "tutelle_curatelle"        => null,
      "don_organes_accord"       => null,
      "directives_anticipees_0"  => null,
      "adresse"                  => "10 rue de la source",
      "cp"                       => "17000",
      "ville"                    => "La Rochelle",
      "pays"                     => "France",
      "tel"                      => "05421242120000",
      "tel2"                     => "06421242120000",
      "__allow_sms_notification" => "yes",
      "email"                    => "ww@justiceleague.us",
      "rques"                    => "Magnis praesidiis diu igitur conmunitam frequentibus idem cum undique conmunitam Pamphyliam petivere magna petivere."
    );

    $page->searchPatientByNameAndFirstname("wonder", "woman");
    $value = $page->createPatientFull($patient_datas, true);
    $this->assertArrayHasKey("anonyme", $value);

    $datas = $page->getPatientDatas();
    $this->assertNotEquals($patient_datas["sexe_f"], $datas["sex"]);
    $this->assertNotEquals($patient_datas["naissance"], $datas["birthday"]);
    $this->assertEmpty($datas["address"], $patient_datas["adresse"]);
    $this->assertEmpty($datas["email"], $patient_datas["email"]);
  }

  /**
   * Vérifie que le nom de naissance obligatoire pour les patients de sexe masculin
   * empêche bien la création du patient
   */
  public function testManLastNameMandatory() {
    $this->dpPage->searchPatientByName("Name");
    $this->dpPage->createPatient("FirstName", $this->patientGender, $this->patientBirthDate);
    $this->assertNotEquals("Patient créé", $this->dpPage->getSystemMessage());
  }

  /**
   * Vérifie que le nom de naissance obligatoire pour les patients de sexe féminin
   * empêche bien la création du patient
   *
   */
  public function testWomanLastNameMandatory() {
    //$this->markTestIncomplete("Alert opened triggers an exception with chrome driver");

    $this->dpPage->searchPatientByName("Name");

    $this->dpPage->createPatient("FirstName", "f", $this->patientBirthDate);

    $this->assertNotEquals("Patient créé", $this->dpPage->getSystemMessage());
  }
}
