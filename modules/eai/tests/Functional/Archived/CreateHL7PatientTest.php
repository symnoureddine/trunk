<?php
/**
 * @package Mediboard\Eai\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Tests\SeleniumTestMediboard;

/**
 * CreateHL7Patient
 *
 * @description Create an addressee and a patient to check if the exchange is correctly generated
 * @screen      EaiPage, dossierPatientPage
 */
class CreateHL7PatientTest extends SeleniumTestMediboard {

  public $nom_destinataire_hl7v2 = "test_destinataire";
  public $code_message_hl7v2     = "A28";
  public $nom_patient            = "TESTEAI";

  /**
   * Création d'un patient et vérification avec un destinataire HL7v2 que l'échange a bien été généré
   *
   * @config [CConfiguration] system object_handlers CSipObjectHandler 1
   * @config [CConfiguration] system object_handlers CSmpObjectHandler 1
   * @config dPpatients CPatient tag_ipp tag
   */
  public function testCreatePatientHL7GenerateEchangeOk() {
    $page = new EaiPage($this);

    $page->createDestinataireHL7v2($this->nom_destinataire_hl7v2);
    $this->assertContains("Destinataire HL7v2 créé", $page->getSystemMessage());

    $page->addExchangeHL7v2($this->nom_destinataire_hl7v2);
    $this->assertContains("Message supporté créé", $page->getSystemMessage());

    $page->addConfigurationReceiver();
    $this->assertContains("Configuration du destinataire HL7v2 créé", $page->getSystemMessage());

    $page->switchModule("dPpatients");
    $dp_page = new DossierPatientPage($this, false);
    $dp_page->searchPatientByName($this->nom_patient);
    $dp_page->createPatient("toto", "m", "27/05/1994");
    $dp_page->getPatientName();

    $dp_page->switchModule("eai");
    $page->switchTab("vw_idx_exchanges");

    $this->assertEquals($this->nom_destinataire_hl7v2, $page->getDestinataireName());
    $this->assertTrue($page->isCodeA28());

    $this->assertTrue($page->isRightPatient($this->nom_patient));
  }

}