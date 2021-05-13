<?php
/**
 * @package Mediboard\Ihe\Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ihe\Tests;

use Ox\Core\CClassMap;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CValue;
use Ox\Interop\Connectathon\CCnStep;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Class CIHETestCase
 * Test Case IHE
 */
class CIHETestCase {
  /**
   * Run test
   *
   * @param string  $code Event code
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function run($code, CCnStep $step) {
    $receiver = $step->_ref_test->loadRefPartner()->loadReceiverHL7v2();

    if ($receiver) {
      CValue::setSessionAbs("cn_receiver_guid", $receiver->_guid);
    }

    $transaction = str_replace("-", "", $step->transaction);

    if (!$transaction) {
      throw new CMbException("CIHETestCase-no_transaction");
    }

    $class_name = "C{$transaction}Test";
    call_user_func(array($class_name, "test$code"), $step);
  }

  /**
   * Load patient PDS
   *
   * @param CCnStep $step        Step
   * @param int     $step_number Step number
   *
   * @throws CMbException
   *
   * @return CPatient $patient
   */
  static function loadPatientPDS(CCnStep $step, $step_number) {
    // PDS-PAM_Identification_Mgt_Merge : Récupération du step 10
    $test    = $step->_ref_test;
    $partner = $test->_ref_partner;

    $patient = new CPatient();
    $where = array();
    $where["nom"] = " = '{$partner->name}_{$test->_id}_$step_number'";
    $patient->loadObject($where);

    if (!$patient->_id) {
      throw new CMbException("CPAM-cn_test-no_patient_id");
    }

    return $patient;
  }

  /**
   * Load patient PES
   *
   * @param CCnStep $step        Step
   * @param int     $step_number Step number
   *
   * @throws CMbException
   *
   * @return CPatient $patient
   */
  static function loadPatientPES(CCnStep $step, $step_number) {
    // PES-PAM_Encounter_Management_Basic
    $test    = $step->_ref_test;
    $partner = $test->_ref_partner;

    $name = null;
    switch ($step_number) {
      case 10 :
        $name = "ONE";
        break;
      case 20 :
        $name = "TWO";
        break;
      case 30 :
        $name = "THREE";
        break;
      case 40 :
        $name = "FOUR";
        break;
      case 50 :
        if ($step->number ==  80) {
          $name = "UPDATE";
        }
        else {
          $name = "FIVE";
        }
        break;
    }
    $name = "PAM$name";

    $patient = new CPatient();
    $where = array();
    $where["nom"] = " = '{$name}_{$partner->name}_{$test->_id}'";
    $patient->loadObject($where);

    if (!$patient->_id) {
      $patient->random();
      $patient->nom = "{$name}_{$partner->name}_{$test->_id}";

      if ($msg = $patient->store()) {
        throw new CMbException($msg);
      }
    }

    return $patient;
  }

  /**
   * Load admit PES
   *
   * @param CPatient $patient Person
   *
   * @throws CMbException
   *
   * @return CSejour $sejour
   */
  static function loadAdmitPES(CPatient $patient) {
    $sejour             = new CSejour();

    $where["patient_id"] = " = '$patient->_id'";
    $where["libelle"]    = " = 'Sejour ITI-31 - $patient->nom'";

    $order = "sejour_id DESC";

    $sejour->loadObject($where, $order);

    if (!$sejour->_id) {
      throw new CMbException("La séjour du patient '$patient->nom' n'a pas été retrouvé");
    }

    return $sejour;
  }

  /**
   * Load leave of absence
   *
   * @param CCnStep $step   Step
   * @param CSejour $sejour Admit
   *
   * @throws CMbException
   *
   * @return CAffectation $affectation
   */
  static function loadLeaveOfAbsence(CCnStep $step, CSejour $sejour) {
    $service_externe = CService::loadServiceExterne($step->_ref_test->group_id);

    if (!$service_externe->_id) {
      throw new CMbException("Aucun service externe de configuré");
    }

    $affectation             = new CAffectation();
    $affectation->service_id = $service_externe->_id;
    $affectation->sejour_id  = $sejour->_id;
    $affectation->entree     = $sejour->entree;
    $affectation->loadMatchingObject();

    if (!$affectation->_id) {
      throw new CMbException("Aucune affectation retrouvée");
    }

    return $affectation;
  }

  /**
   * Store object
   *
   * @param CMbObject $object Object
   *
   * @throws CMbException
   *
   * @return null|string null if successful otherwise returns and error message
   */
  static function storeObject(CMbObject $object) {
    if ($msg = $object->store()) {
      $object->repair();

      if ($msg = $object->store()) {
        throw new CMbException($msg);
      }
    }

    return null;
  }

  /**
   * Delete object
   *
   * @param CMbObject $object Object
   *
   * @throws CMbException
   *
   * @return null|string null if successful otherwise returns and error message
   */
  static function deleteObject(CMbObject $object) {
    if ($msg = $object->delete()) {
      throw new CMbException($msg);
    }

    return null;
  }
}