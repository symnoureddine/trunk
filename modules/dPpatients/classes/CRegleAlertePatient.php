<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use Ox\Core\CAppUI;
use Ox\Core\CStoredObject;
use Ox\Mediboard\Cim10\CCodeCIM10;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Règle concernant l'alerte des évènements patient
 */
class CRegleAlertePatient extends CStoredObject {
  // DB Table key
  public $regle_id;

  // DB fields
  public $group_id;
  public $function_id;
  public $user_id;
  public $name;
  public $age_operateur;
  public $age_valeur;
  public $sexe;
  public $diagnostics;
  public $programme_clinique_id;
  public $nb_anticipation;//Nombre de jours avant date événement
  public $periode_refractaire;//Période réfractaire permettant de ne pas régénérer tous les jours un nouvel évenemenemnt patient
  public $actif;

  //Distant fields
  public $_ext_diagnostics = array();
  public $_ref_users_evt;
  public $_ref_users = array();
  /* @var CProgrammeClinique */
  public $_ref_programme;


  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec        = parent::getSpec();
    $spec->table = 'regle_alerte_patient';
    $spec->key   = 'regle_id';

    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props                          = parent::getProps();
    $props["group_id"]              = "ref class|CGroups autocomplete|text back|regles_alert_etab";
    $props["function_id"]           = "ref class|CFunctions autocomplete|text dependsOn|group_id back|regles_alert_fct";
    $props["user_id"]               = "ref class|CUser autocomplete|_view back|regles_alert_user";
    $props["name"]                  = "str notNull";
    $props["age_operateur"]         = "enum notNull list|sup|inf";
    $props["age_valeur"]            = "num notNull";
    $props["sexe"]                  = "enum list|m|f";
    $props["diagnostics"]           = "text";
    $props["programme_clinique_id"] = "ref class|CProgrammeClinique back|inclusions_regles";
    $props["nb_anticipation"]       = "num notNull min|1";
    $props["periode_refractaire"]   = "num notNull min|1";
    $props["actif"]                 = "bool default|1";

    return $props;
  }

  /**
   * @inheritdoc
   */
  public function check() {
    if (!$this->group_id && !$this->function_id && !$this->user_id) {
      return CAppUI::tr("CRegleAlertePatient.choose_cible");
    }

    return parent::check();
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();
    $this->_view = $this->name;

    // Tokens CIM
    $this->diagnostics = strtoupper($this->diagnostics);
    $diagnostics       = $this->diagnostics ? explode("|", $this->diagnostics) : array();

    // Objets CIM
    foreach ($diagnostics as $code_cim) {
      $this->_ext_diagnostics[$code_cim] = CCodeCIM10::get($code_cim);
    }
  }

  /**
   * Chargement utilisateurs associés à l'alerte
   *
   * @return CMediusers[]|null
   */
  function loadRefsUsers() {
    $this->_ref_users_evt = $this->loadBackRefs("users_alert_evt");
    foreach ($this->_ref_users_evt as $_user_evt) {
      /* @var CEvenementAlerteUser $_user_evt */
      $user = $_user_evt->loadRefUser();
      $user->loadRefFunction();
      $this->_ref_users[$user->_view] = $user;
    }
    ksort($this->_ref_users);

    return $this->_ref_users;
  }

  /**
   * Chargement du programme
   *
   * @return CProgrammeClinique
   */
  function loadRefProgramme() {
    return $this->_ref_programme = $this->loadFwdRef("programme_clinique_id", true);
  }
}