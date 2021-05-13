<?php
/**
 * PAM - ITI-30 - Tests
 *
 * @category IHE
 * @package  Mediboard
 * @author   SARL OpenXtrem <dev@openxtrem.com>
 * @license  GNU General Public License, see http://www.gnu.org/licenses/gpl.html
 * @version  SVN: $Id:$
 * @link     http://www.mediboard.org
 */

namespace Ox\Interop\Ihe\Tests;

use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Interop\Connectathon\CCnStep;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CPatientLink;

/**
 * Class CITI30Test
 * PAM - ITI-30 - Tests
 */
class CITI30Test extends CIHETestCase {
  /**
   * Test A24 - Link the two patients
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA24(CCnStep $step) {
    //  PDS-PAM_Identification_Mgt_Link : Récupération du step 10
    $patient_1 = self::loadPatientPDS($step, 10);

    //  PDS-PAM_Identification_Mgt_Link : Récupération du step 10
    $patient_2 = self::loadPatientPDS($step, 40);

    $patient_1->_doubloon_ids = array($patient_2->_id);

    $patient_1->patient_link_id = $patient_2->_id;

    self::storeObject($patient_1);
  }

  /**
   * Test A28 - Create patient with full demographic data
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA28(CCnStep $step) {
    // PDS-PAM_Identification_Mgt_Merge
    $patient = new CPatient();
    // Random sur les champs du patient
    $patient->random();

    $test    = $step->_ref_test;
    $partner = $test->_ref_partner;

    // On sélectionne le nom du patient en fonction du partenaire, du test et de l'étape
    $patient->nom = "{$partner->name}_{$test->_id}_{$step->number}";

    self::storeObject($patient);
  }

  /**
   * Test A31 - Update patient demographics
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA31(CCnStep $step) {
    // PDS-PAM_Identification_Mgt_Merge : Récupération du step 10
    $patient = self::loadPatientPDS($step, 10);

    $patient->prenom = "CHANGE_$patient->prenom";

    self::storeObject($patient);
  }

  /**
   * Test A37 - Unlink the two previously linked patients
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA37(CCnStep $step) {
    //  PDS-PAM_Identification_Mgt_Link : Récupération du step 10
    $patient_1 = self::loadPatientPDS($step, 10);

    //  PDS-PAM_Identification_Mgt_Link : Récupération du step 10
    $patient_2 = self::loadPatientPDS($step, 40);

    $patient_link = new CPatientLink();
    $where = array(
      "patient_id1" => "= $patient_1->_id",
      "patient_id2" => "= $patient_2->_id",
    );

    $patient_link->loadObject($where);

    if ($patient_link->_id) {
      $patient_link->delete();
    }

    /*$patient->patient_link_id = "";

    self::storeObject($patient);*/
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
    if ($step->transaction == "ITI-30") {
      // PDS-PAM_Identification_Mgt_Merge : Récupération du step 10
      $patient_1        = self::loadPatientPDS($step, 10);
      $first_patient_id = $patient_1->_id;

      // PDS-PAM_Identification_Mgt_Merge : Récupération du step 40
      $patient_2 = self::loadPatientPDS($step, 40);
    }
    else {
      // PES-PAM_Encounter_Management_Basic
      $patient_1        = self::loadPatientPES($step, 10);
      $first_patient_id = $patient_1->_id;

      // PDS-PAM_Identification_Mgt_Merge : Récupération du step 50
      $patient_2 = self::loadPatientPES($step, 50);
    }

    $patient_2_array = array($patient_2);

    $checkMerge = $patient_1->checkMerge($patient_2_array);
    // Erreur sur le check du merge
    if ($checkMerge) {
      throw new CMbException("La fusion de ces deux patients n'est pas possible à cause des problèmes suivants : $checkMerge");
    }

    $patient_1->_id = $first_patient_id;

    $patient_1->_merging = CMbArray::pluck($patient_2_array, "_id");
    if ($msg = $patient_1->merge($patient_2_array)) {
      throw new CMbException($msg);
    }
  }

  /**
   * Test A47 - Changes one of the identifiers
   *
   * @param CCnStep $step Step
   *
   * @throws CMbException
   *
   * @return void
   */
  static function testA47(CCnStep $step) {
    // PDS-PAM_Identification_Mgt_Merge : Récupération du step 10
    $patient = self::loadPatientPDS($step, 10);

    $patient->loadIPP($step->_ref_test->group_id);
    $idex = $patient->_ref_IPP;

    $idex->id400 = rand(1000000, 9999999);

    self::storeObject($idex);
  }
}