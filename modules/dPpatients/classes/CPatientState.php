<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * State of the patient
 */
class CPatientState extends CMbObject {
  /** @var integer Primary key */
  public $patient_state_id;

  static $list_state = array("VIDE", "PROV", "VALI", "DPOT", "ANOM", "CACH", "DOUB", "DESA", "DOUA", "COLP", "COLV", "FILI", "HOMD",
    "HOMA", "USUR", "IDRA", "RECD", "IDVER", "DOUT", "FICTI");

  public $patient_id;
  public $mediuser_id;
  public $state;
  public $datetime;
  public $reason;

  //filter
  public $_date_min;
  public $_date_max;
  public $_number_day;
  public $_date_end;
  public $_merge_patient;

  /** @var CPatient */
  public $_ref_patient;
  /** @var CMediusers */
  public $_ref_mediuser;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "patient_state";
    $spec->key   = "patient_state_id";

    return $spec;
  }

  /**
   * @see parent::getProps();
   */
  function getProps() {
    $props = parent::getProps();

    $props["patient_id"]  = "ref class|CPatient notNull cascade back|patient_state";
    $props["mediuser_id"] = "ref class|CMediusers notNull back|patient_state";
    $props["state"]       = "enum list|" . implode("|", self::$list_state) . " notNull";
    $props["datetime"]    = "dateTime notNull";
    $props["reason"]      = "text";

    //filter
    $props["_date_min"]      = "dateTime";
    $props["_date_max"]      = "dateTime";
    $props["_date_end"]      = "date";
    $props["_number_day"]    = "num";
    $props["_merge_patient"] = "bool default|0";

    return $props;
  }

  /**
   * Load the patient
   *
   * @return CPatient|null
   * @throws \Exception
   */
  function loadRefPatient() {
    return $this->_ref_patient = $this->loadFwdRef("patient_id");
  }

  /**
   * Load the creator of the state
   *
   * @return CMediusers|null
   * @throws \Exception
   */
  function loadRefMediuser() {
    return $this->_ref_mediuser = $this->loadFwdRef("mediuser_id");
  }

  /**
   * Get the number patient by a state and the filter
   *
   * @param String[] $where    Clause
   * @param String[] $leftjoin Jointure
   *
   * @return Int
   * @throws \Exception
   */
  static function getNumberPatient($where, $leftjoin) {
    $ds      = CSQLDataSource::get("std");
    $request = new CRequest();
    $request->addSelect("COUNT(DISTINCT(patients.patient_id))");
    $request->addTable("patients");
    $request->addLJoin($leftjoin);
    $request->addWhere($where);

    return $ds->loadResult($request->makeSelect());
  }

  /**
   * Get all number patient by a state and the filter
   *
   * @param String $date_min Date minimum
   * @param String $date_max Date maximum
   *
   * @return array
   * @throws \Exception
   */
  static function getAllNumberPatient($date_min = null, $date_max = null) {
    $patients_count = array();
    $leftjoin       = null;
    $where          = array();

    if ($date_min) {
      $where[]            = "entree >= '$date_min'";
      $leftjoin["sejour"] = "patients.patient_id = sejour.patient_id";
    }

    if ($date_max) {
      $where[]            = "entree <= '$date_max'";
      $leftjoin["sejour"] = "patients.patient_id = sejour.patient_id";
    }

    $ds      = CSQLDataSource::get("std");
    $request = new CRequest();
    $request->addSelect("`status`, COUNT(DISTINCT(`patients`.`patient_id`)) as `total`");
    $request->addTable("patients");
    $request->addLJoin($leftjoin);
    $request->addWhere($where);
    $request->addGroup("`status`");
    $result      = $ds->loadList($request->makeSelect());
    $state_count = array();
    foreach ($result as $_result) {
      $state_count[$_result["status"]] = $_result["total"];
    }

    foreach (self::$list_state as $_state) {
      $patients_count[CMbString::lower($_state)] = CMbArray::get($state_count, $_state, 0);
      if ($_state == "CACH") {
        $where["vip"]                              = "= '1'";
        $where["status"]                           = "!= 'VALI'";
        $patient                                   = new CPatient();
        $patients_count[CMbString::lower($_state)] = $patient->countList($where, null, $leftjoin);
      }
      if ($_state == "DPOT") {
        $ljoin = [
          "sejour" => "patient_link.patient_id1 = sejour.patient_id"
        ];
        $patient_link                              = new CPatientLink();
        $patients_count[CMbString::lower($_state)] =
          count($patient_link->countMultipleList($where, null, "patient_link.patient_link_id", $ljoin)) * 2;
      }
    }

    return $patients_count;
  }

  /**
   * Store the states of the patient
   *
   * @param CPatient $patient Patient
   *
   * @return null|string
   * @throws \Exception
   */
  static function storeStates($patient) {
    if ($patient->_doubloon_ids) {
      $doubloons = is_array($patient->_doubloon_ids) ? $patient->_doubloon_ids : explode("|", $patient->_doubloon_ids);
      foreach ($doubloons as $_id) {
        $patient_link              = new CPatientLink();
        $patient_link->patient_id1 = $patient->_id;
        $patient_link->patient_id2 = $_id;
        $patient_link->loadMatchingObject();
        $patient_link->store();
      }
    }

    $curr_user = CMediusers::get();

    if ($patient->_homonyme !== null) {
        $patient_state = self::getState($patient, 'HOMD');

        if ($patient->_homonyme) {
            if (!$patient_state->_id) {
                $patient_state->mediuser_id = $curr_user->_id;
                $patient_state->datetime    = 'now';
                self::storeState($patient_state);
            }
        }
        else {
            $patient_state->delete();
        }
    }

    if ($patient->_douteux !== null) {
        $patient_state = self::getState($patient, 'DOUT');
        if ($patient->_douteux) {
            if (!$patient_state->_id) {
                $patient_state->mediuser_id = $curr_user->_id;
                $patient_state->datetime    = 'now';
                $patient_state->store();
            }
        }
        else {
            $patient_state->delete();
        }
    }

    if ($patient->_fictif !== null) {
        $patient_state = self::getState($patient, 'FICTI');
        if ($patient->_fictif) {
            if (!$patient_state->_id) {
                $patient_state->mediuser_id = $curr_user->_id;
                $patient_state->datetime    = 'now';
                $patient_state->store();
            }
        }
        else {
            $patient_state->delete();
        }
    }

    return null;
  }

  public static function getState(CPatient $patient, string $state): self {
      $patient_state = new static();
      $patient_state->patient_id = $patient->_id;
      $patient_state->state = $state;
      $patient_state->loadMatchingObject();
      return $patient_state;
  }

  /**
   * @see parent::store()
   */
  function store() {

    if (!$this->_id) {
      $this->datetime    = $this->datetime ?: CMbDT::dateTime();
      $this->mediuser_id = $this->mediuser_id ?: CMediusers::get()->_id;
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    return null;
  }
}
