<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Mediboard\Cabinet\Tests\Functional\Pages\ComptabilitePage;
use Ox\Mediboard\Cabinet\Tests\Functional\Pages\ConsultationsPage;
use Ox\Mediboard\Cabinet\Tests\Functional\Pages\FacturationPage;
use Ox\Mediboard\Patients\Tests\Functional\Pages\DossierPatientPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * FacturationTest
 *
 * @description Test sur la facturation
 *
 * @screen FacturationPage
 */
class FacturationTest extends SeleniumTestMediboard {
  /** @var FacturationPage $page */
  public $facturationPage;

  public $chir_name = 'CHIRo Testo';
  public $patientLastname = 'PatientLastname';

  /**
   * @inheritdoc
   */
  public function setUpPage() {
    parent::setUpPage();
    $this->facturationPage = new FacturationPage($this);
  }

  /**
   * Nous testons l'ensemble de la facturation Française pour une consultation non issue des urgences:
   * 1- Ajout d'un acte NGAP
   * 2- Cloture de la cotation de consultation générant une facture
   * 3- Vérification du montant total attendu de la facture
   * 4- Visualisation du paiement à réaliser dans la compta
   * 5- Modification de la répartition du montant avec vérification
   * 6- Possibilité d'ajouter un règlement partiel puis total
   * 7- Visualisation des paiements réalisés dans la compta
   *
   * @config ref_pays 1
   * @config [CConfiguration] system object_handlers CPhpUnitHandler 1
   * @config [CConfiguration] dPfacturation CFactureCabinet use_auto_cloture 1
   * @config [CConfiguration] dPfacturation CReglement use_echeancier 0
   * @config [CConfiguration] dPfacturation CRelance use_relances 0
   * @config [CConfiguration] dPccam codage use_cotation_ccam 1
   * @config [CConfiguration] tarmed CCodeTarmed use_cotation_tarmed 0
   */
  public function testFacturationFRConsultationNormale() {
    $this->importObject("dPcabinet/tests/Functional/data/consultation_test.xml");
    $this->facturationPage->switchModule("dPpatients");
    $patientsPage = new DossierPatientPage($this, false);
    $patientsPage->searchPatientByName($this->patientLastname);
    $patientsPage->selectPatientAndConsultation();

    //1- Ajout d'un acte NGAP
    $codage = new ConsultationsPage($this, false);
    $codage->createNGAPact('C', '23');
    $this->assertContains('Acte NGAP créé', $codage->getSystemMessage());

    //2- Cloture de la cotation de consultation générant une facture
    $this->facturationPage->testClotureCotation(true);
    $this->assertContains('Consultation modifiée', $this->facturationPage->getSystemMessage());

    //3- Vérification du montant total attendu de la facture
    $this->assertContains('23', $this->facturationPage->testMontantAreglerPatient());

    //4- Visualisation du paiement à réaliser dans la compta
    //A réaliser

    //5- Modification de la répartition du montant avec vérification
    $this->facturationPage->testChangeRepartitionMontants(12, "tiers");
    $this->assertContains('Facture modifiée', $this->facturationPage->getSystemMessage());
    //Récupération du montant à régler patient pour vérifier que la modification est bonne
    $this->assertContains('11', $this->facturationPage->testMontantAreglerPatient());

    //6- Possibilité d'ajouter un règlement partiel puis total
    $this->facturationPage->testaddReglementPartiel(false, 5);
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());
    $this->facturationPage->testaddReglementTotal();
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());

    //7- Visualisation des paiements réalisés dans la compta
    //A réaliser
  }

  /**
   * Nous testons l'ensemble de la facturation Française pour une consultation issue des urgences:
   * 1- Ajout d'un acte NGAP
   * 2- Cloture de la cotation de consultation générant une facture
   * 3 - Cloture de la facture
   * 4- Vérification du montant total attendu de la facture
   * 5- Visualisation du paiement à réaliser dans la compta
   * 6- Possibilité d'ajouter un règlement partiel puis total
   * 7- Visualisation des paiements réalisés dans la compta
   *
   * @config ref_pays 1
   * @config [CConfiguration] system object_handlers CPhpUnitHandler 1
   * @config [CConfiguration] dPfacturation CFactureEtablissement use_auto_cloture 0
   * @config [CConfiguration] dPfacturation CReglement use_echeancier 0
   * @config [CConfiguration] dPfacturation CRelance use_relances 0
   * @config [CConfiguration] dPccam codage use_cotation_ccam 1
   * @config [CConfiguration] tarmed CCodeTarmed use_cotation_tarmed 0
   */
  public function testFacturationFRConsultationUrgence() {
    $this->importObject("dPcabinet/tests/Functional/data/consultation_urgence_test.xml");
    $this->facturationPage->switchModule("dPpatients");
    $patientsPage = new DossierPatientPage($this, false);
    $patientsPage->searchPatientByName($this->patientLastname);
    $patientsPage->selectPatientAndConsultation();

    //1- Ajout d'un acte NGAP
    $codage = new ConsultationsPage($this, false);
    $codage->createNGAPact('C', '23');
    $this->assertContains('Acte NGAP créé', $codage->getSystemMessage());

    //2- Cloture de la cotation de consultation générant une facture
    $this->facturationPage->testClotureCotation(true);
    $this->assertContains('Consultation modifiée', $this->facturationPage->getSystemMessage());

    //3 - Cloture de la facture
    $this->facturationPage->testClotureFacture();
    $this->assertContains('Facture modifiée', $this->facturationPage->getSystemMessage());

    //4- Vérification du montant total attendu de la facture
    $this->assertContains('23', $this->facturationPage->testMontantAreglerPatient());

    //5- Visualisation du paiement à réaliser dans la compta
    //A réaliser

    //6- Possibilité d'ajouter un règlement partiel puis total
    $this->facturationPage->testaddReglementPartiel(false, 5);
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());
    $this->facturationPage->testaddReglementTotal();
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());

    //7- Visualisation des paiements réalisés dans la compta
    //A réaliser
  }


  /**
   * Nous testons l'ensemble de la facturation Suisse pour une consultation:
   * 1- Ajout d'un acte Tarmed et d'un Caisse
   * 2- Cloture de la cotation de consultation générant une facture
   * 3- Modification du type de la facture
   * 4- Cloture de la  facture
   * 5- Vérification du montant total attendu de la facture
   * 6- Visualisation du paiement à réaliser dans la compta
   * 7- Possibilité d'ajouter un règlement partiel puis total
   * 8- Visualisation des paiements réalisés dans la compta
   *
   * @config ref_pays 2
   * @config [CConfiguration] dPccam codage use_cotation_ccam 0
   * @config [CConfiguration] tarmed CCodeTarmed use_cotation_tarmed 1
   * @config [CConfiguration] dPfacturation CFactureCabinet use_auto_cloture 0
   */
  public function testFacturationCHConsultation() {
    $this->importObject("dPcabinet/tests/Functional/data/consultation_test.xml");
    $this->facturationPage->switchModule("dPpatients");
    $patientsPage = new DossierPatientPage($this, false);
    $patientsPage->searchPatientByName($this->patientLastname);
    $patientsPage->selectPatientAndConsultation();

    //1- Ajout d'un acte Tarmed
    $codage = new ConsultationsPage($this, false);
    $codage->createTarmedact('00.0010');
    $this->assertContains('Acte Tarmed crée', $codage->getSystemMessage());

    //2- Cloture de la cotation de consultation générant une facture
    $this->facturationPage->testClotureCotation(true);
    $this->assertContains('Consultation modifiée', $this->facturationPage->getSystemMessage());

    //3- Modification du type de la facture
    $this->facturationPage->testChangeTypeFacture();
    $this->assertContains('Facture modifiée', $this->facturationPage->getSystemMessage());

    //4- Cloture de la facture
    $this->facturationPage->testClotureFacture();
    $this->assertContains('Facture modifiée', $this->facturationPage->getSystemMessage());

    //5- Vérification du montant total attendu de la facture (coefficient / pt_maladie)
    //Impossible tant que je ne se saurais pas mettre par défaut via @config une configuration par fonction

    //6- Visualisation du paiement à réaliser dans la compta
    //A réaliser

    //7- Possibilité d'ajouter un règlement partiel puis total
    $this->facturationPage->testaddReglementPartiel(true, 5);
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());
    $this->facturationPage->testaddReglementTotal(true);
    $this->assertContains('Règlement créé', $this->facturationPage->getSystemMessage());

    //8 Visualisation des paiements réalisés dans la compta
    //A réaliser
  }
}