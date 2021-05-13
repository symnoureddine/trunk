<?php
/**
 * PAM - ITI-31 - Tests
 *
 * @category IHE
 * @package  Mediboard
 * @author   SARL OpenXtrem <dev@openxtrem.com>
 * @license  GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @version  SVN: $Id:$
 * @link     http://www.mediboard.org
 */

namespace Ox\Interop\Ihe\Tests;

use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Interop\Connectathon\CCnStep;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Hospi\CLit;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Class CITI31Test
 * PAM - ITI-31 - Tests
 */
class CITI31Test extends CIHETestCase {
  /**
   * Test A01 - Admit inpatient
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA01(CCnStep $step) {
    $patient = self::loadPatientPES($step, $step->number);

    $scenario = $step->_ref_test->_ref_scenario;

    $sejour              = new CSejour();
    $sejour->patient_id  = $patient->_id;
    $sejour->group_id    = $step->_ref_test->group_id;

    $timestamp = time() + (rand(1, 30) * rand(1, 24) * rand(1, 60) * rand(1, 60));

    switch ($scenario->option) {
      case 'HISTORIC_MVT' :
        $sejour->entree_prevue = strftime(CMbDT::ISO_DATE, $timestamp)." 08:00:00";
        break;

      default :
        $sejour->entree_prevue = strftime(CMbDT::ISO_DATETIME, $timestamp);
        break;
    }

    $sejour->entree_reelle = $sejour->entree_prevue;
    $sejour->sortie_prevue = CMbDT::dateTime("+4 day", $sejour->entree_reelle);
    $sejour->praticien_id  = $sejour->getRandomValue("praticien_id", true);
    $sejour->type          = "comp";
    $sejour->service_id    = $sejour->getRandomValue("service_id", true);
    $sejour->libelle       = "Sejour ITI-31 - $patient->nom";
    $sejour->mode_sortie   = "";

    self::storeObject($sejour);
  }

  /**
   * Test A02 - Transfer the patient to a new room
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA02(CCnStep $step) {
    // PES-PAM_Encounter_Management_IN_OUT
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $lit = new CLit();
    $lit->loadMatchingObject();

    $affectation             = new CAffectation();
    $affectation->lit_id     = $lit->_id;
    $affectation->sejour_id  = $sejour->_id;
    $affectation->entree     = $sejour->entree;
    $affectation->sortie     = CMbDT::dateTime("+2 day", $affectation->entree);

    self::storeObject($affectation);
  }

  /**
   * Test A03 - Discharge patient
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA03(CCnStep $step) {
    $scenario = $step->_ref_test->_ref_scenario;

    $step_number = null;
    switch ($scenario->option) {
      case 'HISTORIC_MVT':
        $step_number = 10;

        break;

      default:
        if ($step->number == 70) {
          $step_number = 30;
        }
        if ($step->number == 80) {
          $step_number = 40;
        }

        break;
    }

    if (!$step_number) {
      throw new CMbException("Aucune étape trouvée");
    }

    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, $step_number);
    $sejour  = self::loadAdmitPES($patient);

    if ($scenario->option == "HISTORIC_MVT" && $step_number == "10") {
      $sortie_reelle = CMbDT::date($sejour->sortie_prevue) . " 12:00:00";
    }

    $sejour->sortie_reelle = $sortie_reelle ? $sortie_reelle : $sejour->sortie_prevue;

    self::storeObject($sejour);
  }

  /**
   * Test A04 - Admit outpatient
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA04(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, $step->number);

    $sejour                = new CSejour();
    $sejour->patient_id    = $patient->_id;
    $sejour->group_id      = $step->_ref_test->group_id;

    $timestamp = time() + (rand(1, 30) * rand(1, 24) * rand(1, 60) * rand(1, 60));

    $sejour->entree_prevue = strftime(CMbDT::ISO_DATETIME, $timestamp);
    $sejour->entree_reelle = $sejour->entree_prevue;
    $sejour->sortie_prevue = CMbDT::dateTime("+6 hours", $sejour->entree_reelle);
    $sejour->praticien_id  = $sejour->getRandomValue("praticien_id", true);
    $sejour->type          = "urg";
    $sejour->service_id    = $sejour->getRandomValue("service_id", true);
    $sejour->libelle       = "Sejour ITI-31 - $patient->nom";
    $sejour->mode_sortie   = "";

    self::storeObject($sejour);
  }

  /**
   * Test A05 - Pre-admit the inpatient
   *
   * @param CCnStep $step         Step
   * @param bool    $notification Notification
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA05(CCnStep $step, $notification = false) {
    $patient = self::loadPatientPES($step, $step->number);

    $sejour              = new CSejour();
    $sejour->patient_id  = $patient->_id;
    $sejour->group_id    = $step->_ref_test->group_id;

    $timestamp = time() + (rand(1, 30) * rand(1, 24) * rand(1, 60) * rand(1, 60));

    $sejour->entree_prevue = strftime(CMbDT::ISO_DATETIME, $timestamp);
    $sejour->sortie_prevue = CMbDT::dateTime("+4 day", $sejour->entree_prevue);
    $sejour->praticien_id  = $sejour->getRandomValue("praticien_id", true);
    $sejour->type          = "comp";
    $sejour->service_id    = $sejour->getRandomValue("service_id", true);
    $sejour->libelle       = "Sejour ITI-31 - $patient->nom";

    // Pending admit
    if ($notification) {
      $sejour->recuse = -1;
    }

    self::storeObject($sejour);
  }

  /**
   * Test A06 - Change patient's class from outpatient (PV1-2 = O) to inpatient (PV1-2 = I)
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA06(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->type = "comp";

    self::storeObject($sejour);
  }

  /**
   * Test A07 - Change patient's class from inpatient (PV1-2 = I) to outpatient (PV1-2 = O)
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA07(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->type = "exte";

    self::storeObject($sejour);
  }

  /**
   * Test A08 - Update last name
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA08(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, 50);
    $sejour  = self::loadAdmitPES($patient);

    $patient->nom = str_replace("PAMFIVE", "PAMUPDATE", $patient->nom);

    if ($msg = $patient->store()) {
      throw new CMbException($msg);
    }

    $sejour->libelle = "Sejour ITI-31 - $patient->nom";

    self::storeObject($sejour);
  }

  /**
   * Test A11 - Cancel visit
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA11(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    dump("testA11");
    $patient = self::loadPatientPES($step, 20);

    dump($patient);
    $sejour = self::loadAdmitPES($patient);

    dump($sejour->_id);

    $sejour->entree_reelle = "";

    self::storeObject($sejour);
  }

  /**
   * Test A12 - Cancel the previous transfer
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA12(CCnStep $step) {
    // PES-PAM_Encounter_Management_IN_OUT
    $patient     = self::loadPatientPES($step, 40);
    $sejour      = self::loadAdmitPES($patient);
    $affectation = $sejour->loadRefFirstAffectation();

    self::deleteObject($affectation);
  }

  /**
   * Test A13 - Cancel discharge
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA13(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, 30);
    $sejour = self::loadAdmitPES($patient);

    $sejour->sortie_reelle = "";

    self::storeObject($sejour);
  }

  /**
   * Test A14 - Pending Admit
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA14(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING

    self::testA05($step, true);
  }

  /**
   * Test A15 - Pending Transfer
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA15(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->mode_sortie = "transfert";

    self::storeObject($sejour);
  }

  /**
   * Test A16 - Pending Discharge
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA16(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->confirme = $sejour->sortie;

    self::storeObject($sejour);
  }

  /**
   * Test A21 - Gone on a leave of absence
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA21(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $step_number = null;
    $add_day     = 0;
    if ($step->number == 60) {
      $add_day     = 1;
      $step_number = 30;
    }
    if ($step->number == 70) {
      $add_day     = 2;
      $step_number = 20;
    }

    if (!$step_number) {
      throw new CMbException("Aucune étape trouvée");
    }

    $patient = self::loadPatientPES($step, $step_number);
    $sejour  = self::loadAdmitPES($patient);

    $service_externe           = new CService();
    $service_externe->group_id = $step->_ref_test->group_id;
    $service_externe->externe  = 1;
    $service_externe->loadMatchingObject();

    if (!$service_externe->_id) {
      throw new CMbException("Aucun service externe de configuré");
    }

    $affectation             = new CAffectation();
    $affectation->service_id = $service_externe->_id;
    $affectation->sejour_id  = $sejour->_id;
    $affectation->entree     = $sejour->entree;
    $affectation->sortie     = CMbDT::dateTime("+$add_day day", $affectation->entree);

    self::storeObject($affectation);
  }

  /**
   * Test A22 - Returned from its leave of absence
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA22(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient     = self::loadPatientPES($step, 30);
    $sejour      = self::loadAdmitPES($patient);
    $affectation = self::loadLeaveOfAbsence($step, $sejour);

    $affectation->effectue   = 1;

    self::storeObject($affectation);
  }

  /**
   * Test A25 - Cancel Pending Discharge
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA25(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->confirme = "";

    self::storeObject($sejour);
  }

  /**
   * Test A26 - Cancel Pending Transfer
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA26(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING
    $patient = self::loadPatientPES($step, 40);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->mode_sortie = "";

    self::storeObject($sejour);
  }

  /**
   * Test A27 - Cancel Pending Admit
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA27(CCnStep $step) {
    // PES-PAM_Encounter_Management_PENDING
    $patient = self::loadPatientPES($step, 20);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->annule = 1;

    self::storeObject($sejour);
  }

  /**
   * Test A38 - Cancel the pre-admission
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA38(CCnStep $step) {
    // PES-PAM_Encounter_Management_Basic
    $patient = self::loadPatientPES($step, 20);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->annule = 1;

    self::storeObject($sejour);
  }

  /**
   * Test A40 - Merge the two patients
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA40(CCnStep $step) {
    CITI30Test::testA40($step);
  }

  /**
   * Test A44 - Moves the account of patient#1 to patient#2
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA44(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient_1   = self::loadPatientPES($step, 20);
    $patient_2   = self::loadPatientPES($step, 30);
    $sejour      = self::loadAdmitPES($patient_2);

    $sejour->patient_id = $patient_1->_id;

    self::storeObject($sejour);
  }

  /**
   * Test A52 - Cancel the leave of absence
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA52(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient     = self::loadPatientPES($step, 20);
    $sejour      = self::loadAdmitPES($patient);
    $affectation = self::loadLeaveOfAbsence($step, $sejour);

    self::deleteObject($affectation);
  }

  /**
   * Test A53 - Cancel the return from leave of absence
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA53(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient     = self::loadPatientPES($step, 30);
    $sejour      = self::loadAdmitPES($patient);
    $affectation = self::loadLeaveOfAbsence($step, $sejour);

    $affectation->effectue   = 0;

    self::storeObject($affectation);
  }

  /**
   * Test A54 - Change the name of the attending doctor
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA54(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient = self::loadPatientPES($step, 20);
    $sejour  = self::loadAdmitPES($patient);

    do {
      $random_value = $sejour->getRandomValue("praticien_id", true);
    } while ($sejour->praticien_id == $random_value);

    $sejour->praticien_id = $random_value;

    self::storeObject($sejour);
  }

  /**
   * Test A55 - Change back the name of the attending doctor to the original one
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA55(CCnStep $step) {
    // PES-PAM_Encounter_Management_ADVANCE
    $patient = self::loadPatientPES($step, 20);
    $sejour  = self::loadAdmitPES($patient);

    $sejour->praticien_id = $sejour->getValueAtDate($sejour->loadFirstLog()->date, "praticien_id");

    self::storeObject($sejour);
  }

  /**
   * Test Z99 - Update admit
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testZ99(CCnStep $step) {
    $patient = self::loadPatientPES($step, 10);
    $sejour  = self::loadAdmitPES($patient);

    $scenario = $step->_ref_test->_ref_scenario;

    switch ($scenario->option) {
      case 'HISTORIC_MVT' :
        if ($step->number == 30) {
          $sejour->sortie_reelle = CMbDT::date($sejour->sortie)." 11:00:00";
        }
        if ($step->number == 40) {
          $sejour->entree_reelle = CMbDT::date($sejour->entree_reelle)." 07:30:00";
        }
        break;

      default :

        break;
    }

    self::storeObject($sejour);
  }
}