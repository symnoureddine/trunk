<?php
/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Personnel;

use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Class CPersonnel
 */
class CPersonnel extends CMbObject {
  // DB Table key
  public $personnel_id;

  // DB references
  public $user_id;
  public $_ref_user;

  // DB fields
  public $emplacement;
  public $actif;

  // Form Field
  public $_user_last_name;
  public $_user_first_name;
  public $_emplacements;

  /** @var array The list of types */
  public static $_types = array(
    'op',
    'op_panseuse',
    'reveil',
    'service',
    'iade',
    'brancardier',
    'sagefemme',
    'manipulateur',
    'aux_puericulture',
    'instrumentiste',
    'circulante',
    'aide_soignant'
  );

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = "personnel";
    $spec->key   = "personnel_id";

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props                = parent::getProps();
    $props["user_id"]     = "ref notNull class|CMediusers back|personnels";
    $props["emplacement"] = "enum notNull list|" . implode('|', self::$_types) . " default|op";
    $props["actif"]       = "bool notNull default|1";

    $props["_user_last_name"]  = "str";
    $props["_user_first_name"] = "str";

    return $props;
  }

  /**
   * @see parent::loadRefsFwd()
   */
  function loadRefsFwd() {
    parent::loadRefsFwd();
    $this->loadRefUser();
  }

  /**
   * Load User
   *
   * @return CMediusers|null
   * @throws Exception
   */
  function loadRefUser() {
    $this->_ref_user = $this->loadFwdRef("user_id", true);
    $this->_view     = $this->getFormattedValue("emplacement") . ": " . $this->_ref_user->_view;

    return $this->_ref_user;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->getFormattedValue("emplacement") . ": " . $this->user_id;
  }

  /**
   * Load list overlay for current group
   *
   * @param array $where   where
   * @param array $order   order
   * @param int   $limit   limit
   * @param array $groupby groupby
   * @param array $ljoin   ljoin
   *
   * @return self[]
   */
  function loadGroupList($where = array(), $order = null, $limit = null, $groupby = null, $ljoin = array()) {
    $ljoin["users_mediboard"]     = "users_mediboard.user_id = personnel.user_id";
    $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";
    $ljoin["secondary_function"]  = "secondary_function.user_id = users_mediboard.user_id";
    $ljoin[]                      = "functions_mediboard secondary_function_B ON secondary_function_B.function_id = secondary_function.function_id";
    // Filtre sur l'établissement
    $g       = CGroups::loadCurrent();
    $where[] = "functions_mediboard.group_id = '$g->_id' OR secondary_function_B.group_id = '$g->_id'";

    $list = $this->loadList($where, $order, $limit, $groupby, $ljoin);

    /* The load list can return null if the dPpersonnel module is not installed */
    if (!is_array($list)) {
      $list = array();
    }

    return $list;
  }

  /**
   * Charge le personnel pour l'établissement courant
   *
   * @param string $emplacement Emplacement du personnel
   * @param bool   $actif       Seulement les actifs
   * @param bool   $groupby     Grouper par utilisateur
   *
   * @return self[]
   */
  static function loadListPers($emplacement, $actif = true, $groupby = false) {
    $personnel = new self();

    $where = array();

    if (is_array($emplacement)) {
      $where["emplacement"] = CSQLDataSource::prepareIn($emplacement);
    } else {
      $where["emplacement"] = "= '$emplacement'";
    }

    // Could have been ambiguous with CMediusers.actif
    if ($actif) {
      $where["personnel.actif"]       = "= '1'";
      $where["users_mediboard.actif"] = "= '1'";
    }

    $ljoin["users"]           = "personnel.user_id = users.user_id";
    $ljoin["users_mediboard"] = "users_mediboard.user_id = users.user_id";

    $order = "users.user_last_name";

    $group = "personnel" . ($groupby ? ".user_id" : ".personnel_id");

    /** @var self[] $personnels */
    $personnels = $personnel->loadGroupList($where, $order, null, $group, $ljoin);
    $users      = CStoredObject::massLoadFwdRef($personnels, "user_id");
    CStoredObject::massLoadFwdRef($users, "function_id");

    self::massLoadListEmplacement($personnels);

    foreach ($personnels as $_personnel) {
      $_personnel->loadRefUser()->loadRefFunction();
    }

    return $personnels;
  }


  /**
   * Recherche de l'ensemble des emplacements de l'utilisateur
   *
   * @return array
   */
  function loadListEmplacement() {
    $ds           = $this->getDS();
    $query        = "SELECT DISTINCT emplacement
      FROM personnel
      WHERE user_id = '$this->user_id'
      AND actif = '1';";
    $emplacements = $ds->loadList($query);

    $list_emplacements = array();
    foreach ($emplacements as $_emplacement) {
      $list_emplacements[$_emplacement["emplacement"]] = $_emplacement["emplacement"];
    }

    return $this->_emplacements = $list_emplacements;
  }

  static function massLoadListEmplacement($personnels = array()) {
    if (!count($personnels)) {
      return;
    }

    $ds = CSQLDataSource::get("std");

    $query        = "SELECT user_id, GROUP_CONCAT(DISTINCT emplacement) AS emplacements
      FROM personnel
      WHERE user_id " . CSQLDataSource::prepareIn(CMbArray::pluck($personnels, "user_id")) . "
      AND actif = '1'
      GROUP BY user_id;";
    $emplacements = $ds->loadHashAssoc($query);

    foreach ($emplacements as $user_id => $_emplacement) {
      $_emplacements = explode(",", $_emplacement["emplacements"]);

      foreach ($personnels as $_personnel_id => $_personnel) {
        if ($_personnel->user_id != $user_id) {
          continue;
        }

        $personnels[$_personnel_id]->_emplacements = array_combine($_emplacements, $_emplacements);
      }
    }
  }
}
