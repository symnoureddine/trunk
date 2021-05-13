<?php
/**
 * @package Mediboard\Cabinet\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Mediboard\Cabinet\Tests\Functional\Pages\ConsultationsPage;
use Ox\Tests\SeleniumTestMediboard;

/**
 * Tests on the CCAM and NGAP acts in the consultation
 *
 * @description Test the creation of acts (CCAM and NGAP) on the consultations
 *
 * @screen      ConsultationPage
 */
class CodageConsultationTest extends SeleniumTestMediboard
{

    /** @var ConsultationsPage $page */
    public $consultationPage;

    public $chir_name       = 'CHIR Test';
    public $patientLastname = 'PatientLastname';

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->consultationPage = new ConsultationsPage($this);
        $this->importObject("dPcabinet/tests/Functional/data/patient_test.xml");
    }

    /**
     * Teste la création d'un acte NGAP
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCreateNGAPAct()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->createNGAPact('C', '23');
        $this->assertContains('Acte NGAP créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la cloture de la cotation et la création d'un tarif
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCreateTarif()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->createNGAPact('C', '23');
        $this->consultationPage->closeModal();
        $this->consultationPage->closeCotation();
        $this->assertContains('Consultation modifiée', $this->consultationPage->getSystemMessage());
        $this->consultationPage->createTarifConsult();
        $this->assertContains('Tarif créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste l'ajout d'un code CCAM sur une consultation (en mode nouveau codage)
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testAddCodeCCAM()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->addCodeCCAM('NFEP001');
        $this->assertContains('Consultation modifiée', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la création d'un acte CCAM (en mode nouveau codage)
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCreateCCAMAct()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->addCodeCCAM('AAFA001');
        $this->consultationPage->createCCAMAct('AAFA001', 1, 0);
        $this->assertContains('Acte CCAM créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la validation d'un codage CCAM
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testValidateCCAMCodage()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->addCodeCCAM('AAFA001');
        $this->consultationPage->createCCAMAct('AAFA001', 1, 0);
        $this->consultationPage->getSystemMessage();
        $this->consultationPage->validateCCAMCodage();
        $this->assertContains('Codage CCAM validé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la création d'un acte LPP
     *
     * @config lpp cotation_lpp 1
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCreateLPPAct()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate($this->chir_name);
        $this->consultationPage->createLPPAct('1158737');
        $this->assertContains('Acte LPP créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Test la création d'un tarif avec un acte CAM et un acte NGAP
     *
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCreateTarifInView()
    {
        $this->consultationPage->openViewCreateTarif();
        $this->consultationPage->setTarifActs('NFEP002', 1, 0, 'C', 23);
        $this->consultationPage->getSystemMessage();

        if (CMbDT::time() >= "00:00:00" && CMbDT::time() < "08:00:00") {
            $assertString = 'NFEP002-1-0-S---1--0----';
        } else {
            if (array_key_exists(CMbDT::date(), CMbDT::getHolidays())) {
                $assertString = 'NFEP002-1-0-F---1--0----';
            } else {
                $assertString = 'NFEP002-1-0----1--0----';
            }
        }
        $this->assertEquals($assertString, $this->consultationPage->getTarifCCAMActValue());
        $this->assertContains('1-C-1-23--0--0', $this->consultationPage->getTarifNGAPActValue());
        $this->consultationPage->createTarif();
        $this->assertContains('Tarif créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la création d'acte NGAP dans une consultation pour une sage femme
     *
     * @pref   take_consult_for_sage_femme 1
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCodageSageFemme()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate('SAGE Femme');
        $this->consultationPage->createNGAPact('SF', '2.80');
        $this->assertContains('Acte NGAP créé', $this->consultationPage->getSystemMessage());
    }

    /**
     * Teste la création d'acte NGAP dans une consultation pour un kiné
     *
     * @pref   take_consult_for_reeducateur 1
     * @config dPccam codage use_cotation_ccam 1
     */
    public function testCodageKine()
    {
        $this->consultationPage->switchModule("dPpatients");
        $patientsPage = new DossierPatientPage($this, false);
        $patientsPage->searchPatientByName($this->patientLastname);
        $patientsPage->createConsultationImmediate('KINESI Therapeute');
        $this->consultationPage->createNGAPact('AMK', '2.15');
        $this->assertContains('Acte NGAP créé', $this->consultationPage->getSystemMessage());
    }
}
