<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Astreintes;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CPlageCalendaire;
use Ox\Core\CStoredObject;
use Ox\Core\FieldSpecs\CColorSpec;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;


class CPlageAstreinte extends CPlageCalendaire {
  // DB Table key
  static $astreintes_type = array(
    "admin",
    "informatique",
    "medical",
    "personnelsoignant",
    "technique"
  );

  // DB Fields
  public $plage_id;
  public $libelle;
  public $user_id;
  public $group_id;
  public $type;
  public $choose_astreinte;
  public $color;
  public $phone_astreinte;
  public $categorie;

  // available types
  public $locked;

  // Object References
  public $_num_astreinte;

  /** @var CMediusers $_ref_user */
  public $_ref_user;
  /** @var CGroups $_ref_group */
  public $_ref_group;
  /** @var CCategorieAstreinte */
  public $_ref_category;

  // Form fields
  public $_duree;   //00:00:00
  public $_hours;   // 29.5 hours
  public $_duration;
  public $_color;
  public $_font_color;
  public $_type_repeat;
  public $_repeat_week;
  public $_count_duplicated_plages = 0;

  /** Behaviour fields
   *
   * @return string $specs
   *
   */
  function getSpec() {
    $specs                 = parent::getSpec();
    $specs->table          = "astreinte_plage";
    $specs->key            = "plage_id";
    $specs->collision_keys = array("type", "user_id");

    return $specs;
  }

  /**
   * spécification des propriétés
   *
   * @return array $specs
   **/
  function getProps() {
    $specs                     = parent::getProps();
    $specs["user_id"]          = "ref class|CMediusers notNull back|astreintes";
    $specs["type"]             = "enum list|" . implode("|", self::$astreintes_type) . " notNull";
    $specs["color"]            = "color";
    $specs["choose_astreinte"] = "enum list|ponctuelle|reguliere default|ponctuelle notNull";
    $specs["libelle"]          = "str";
    $specs["group_id"]         = "ref class|CGroups notNull back|group_astreinte";
    $specs["phone_astreinte"]  = "str notNull";
    $specs["categorie"]        = "ref class|CCategorieAstreinte back|shifts";
    $specs["locked"]           = "bool default|0";

    // Form fields
    $specs["_type_repeat"] = "enum list|simple|double|triple|quadruple|sameweek";

    return $specs;
  }

  function store() {
    // A person can be on call on different services
    $this->_skip_collision_check = true;

    return parent::store();
  }

  function loadRefGroup() {
    return $this->_ref_group = $this->loadFwdRef("group_id", true);
  }

  /**
   * loadView
   *
   * @return null
   */
  function loadView() {
    parent::loadView();
    $this->getDuree();
    $this->getDuration();
    $this->_ref_user = $this->loadRefUser();  //I need the Phone Astreinte
  }

  /**
   * get the duration
   *
   * @return string
   */
  function getDuree() {
    return $this->_duree = CMbDT::duration($this->start, $this->end, 0);
  }

  /**
   * get duration for the current plage
   *
   * @return array
   */
  function getDuration() {
    return $this->_duration = CMbDT::duration($this->start, $this->end);
  }

  /**
   * Load ref user
   *
   * @return CMediusers
   */
  function loadRefUser() {
    /** @var CMediusers $mediuser */
    $mediuser = $this->loadFwdRef("user_id", true);
    $mediuser->loadRefFunction();

    $this->_num_astreinte = $mediuser->_user_astreinte;

    return $this->_ref_user = $mediuser;
  }

  /**
   * load list of Astreinte for a specified range
   *
   * @param int    $user_id user_id
   * @param string $min     date min
   * @param string $max     date max
   *
   * @return CStoredObject[]
   */
  function loadListForRange($user_id, $min, $max) {
    $where["user_id"] = "= '$user_id'";
    $where["start"]   = "<= '$max'";
    $where["end"]     = ">= '$min'";
    $order            = "end";

    return $this->loadList($where, $order);
  }

  /**
   * get the permission type
   *
   * @param int $permType permission type
   *
   * @return bool
   */
  function getPerm($permType) {
    if (!$this->_ref_user) {
      $this->loadRefUser();
    }

    if (CAppUI::$user->isAdmin()) {
      return true;
    }

    if (CModule::getCanDo('astreintes')->edit && $this->_ref_user->getPerm($permType)) {
      return true;
    }

    /* @todo À quoi sert ce droit ? */
    if (CModule::getCanDo("astreintes")->read && $permType <= PERM_READ) {
      return true;
    }

    return false;
  }

  /**
   * get the number of hours between start & end
   *
   * @return float
   */
  function getHours() {
    return $this->_hours = CMbDT::minutesRelative($this->start, $this->end) / 60;
  }

  /**
   * Load color for astreinte
   *
   * @return mixed
   */
  function loadRefColor() {
    $color             = CAppUI::gconf("astreintes General astreinte_" . $this->type . "_color");
    $this->_font_color = CColorSpec::get_text_color($color) > 130 ? '000000' : 'ffffff';

    if ($this->color) {
      return $this->_color = $this->color;
    }
    if ($this->categorie) {
      self::loadRefCategory();

      return $this->_color = $this->_ref_category->color;
    }

    return $this->_color = str_replace("#",
      "",
      CAppUI::gconf("astreintes General astreinte_" . $this->type . "_color"));
  }

  /**
   * Loads an 'on call' category object
   *
   * @return CCategorieAstreinte
   */
  function loadRefCategory() {
    return $this->_ref_category = $this->loadFwdRef("categorie");
  }

  /**
   * Load phone for astreinte
   *
   * @return CMbObject
   */
  function loadRefPhoneAstreinte() {
    return $this->_num_astreinte = $this->loadFwdRef("_user_astreinte", true);
  }

  /**
   * Find the next plageAstreinte according
   * to the current plageAstreinte parameters
   * return the number of weeks jumped
   *
   * @param int $init_user_id Utilisateur intial
   *
   * @return int
   */
  function becomeNext($init_user_id = null) {
    $week_jumped = 0;
    if (!$this->_type_repeat) {
      $this->_type_repeat = "simple";
    }

    switch ($this->_type_repeat) {
      case "quadruple":
        $this->start = CMbDT::dateTime("+4 WEEK", $this->start);
        $this->end   = CMbDT::dateTime("+4 WEEK", $this->end);
        $week_jumped += 4;
        break;
      case "triple":
        $this->start = CMbDT::dateTime("+3 WEEK", $this->start);
        $this->end   = CMbDT::dateTime("+3 WEEK", $this->end);
        $week_jumped += 3;
        break;
      case "double":
        $this->start = CMbDT::dateTime("+2 WEEK", $this->start);
        $this->end   = CMbDT::dateTime("+2 WEEK", $this->end);
        $week_jumped += 2;
        break;
      case "simple":
        $this->start = CMbDT::dateTime("+1 WEEK", $this->start);
        $this->end   = CMbDT::dateTime("+1 WEEK", $this->end);
        $week_jumped++;
        break;
    }

    // Stockage des champs modifiés
    $choose_astreinte = $this->choose_astreinte;
    $user_id          = $this->user_id;
    $start            = $this->start;
    $end              = $this->end;
    $libelle          = $this->libelle;
    $categorie        = $this->categorie;
    $group_id         = $this->group_id;
    $color            = $this->color;
    $type             = $this->type;
    $phone_astreinte  = $this->phone_astreinte;

    // Recherche de la plage suivante
    $where            = array();
    $where[]          = "`start` = '$this->start' OR `end` = '$this->end'";
    $where["user_id"] = $init_user_id ? "= '$init_user_id'" : "= '$this->user_id'";

    $plages_astreintes = $this->loadList($where);

    if (count($plages_astreintes) > 0) {
      $this->load(reset($plages_astreintes)->plage_id);
    }
    else {
      $this->plage_id = null;
    }

    // Remise en place des champs modifiés
    $this->choose_astreinte = $choose_astreinte;
    $this->user_id          = $user_id;
    $this->start            = $start;
    $this->end              = $end;
    $this->libelle          = $libelle;
    $this->categorie        = $categorie;
    $this->group_id         = $group_id;
    $this->color            = $color;
    $this->type             = $type;
    $this->phone_astreinte  = $phone_astreinte;
    $this->updateFormFields();

    return $week_jumped;
  }

  /**
   * Count the number of CPlageAstreinte duplicated from the current CPlageAstreinte
   *
   * @return int
   */
  function countDuplicatedPlages() {
    $where = array(
      'user_id'          => " = $this->user_id",
      'type'             => " = '$this->type'",
      'choose_astreinte' => " = '$this->choose_astreinte'",
      'phone_astreinte'  => " = '$this->phone_astreinte'",
      'start'            => " > '$this->start'",
      'end'              => " > '$this->end'"
    );

    return $this->_count_duplicated_plages = $this->countList($where);
  }
}
