<?php
/**
 * @package Mediboard\Sante400
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Sante400;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbRange;
use Ox\Core\Mutex\CMbMutex;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Eai\CGroupDomain;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

class CIncrementer extends CMbObject {
  // DB Table key
  public $incrementer_id;

  // DB fields
  public $last_update;
  public $value;
  public $pattern;
  // range
  public $manage_range;
  public $range_min;
  public $range_max;
  // init
  public $extra_data;
  public $reset_value;

  // Form fields
  public $_object_class;

  /** @var CDomain */
  public $_ref_domain;

  /** @var CGroupDomain */
  public $_ref_group_domain;

  function getSpec() {
    $spec = parent::getSpec();

    $spec->table    = 'incrementer';
    $spec->key      = 'incrementer_id';
    $spec->loggable = false;

    return $spec;
  }

  function getProps() {
    $props = parent::getProps();

    $props["value"]        = "str notNull default|1";
    $props["pattern"]      = "str notNull";
    $props["manage_range"] = "bool default|0";
    $props["range_min"]    = "num min|0";
    $props["range_max"]    = "num moreThan|range_min";
    $props["last_update"]  = "dateTime notNull";
    $props["extra_data"]   = "str maxLength|10 protected";
    $props["reset_value"]  = "num min|0";

    $props["_object_class"] = "enum notNull list|CPatient|CSejour";

    return $props;
  }

  /**
   * @return CDomain
   * @throws Exception
   */
  function loadRefDomain() {
    if ($this->_ref_domain) {
      return $this->_ref_domain;
    }

    return $this->_ref_domain = $this->loadUniqueBackRef("domains");
  }

  /**
   * Load the master domain
   *
   * @param String $domain_id identifiant domain
   *
   * @return CGroupDomain
   */
  function loadMasterDomain($domain_id = null) {
    if ($this->_ref_group_domain) {
      return $this->_ref_group_domain;
    }

    $domain = new CDomain();
    $domain = $domain_id ? $domain->load($domain_id) : $this->loadRefDomain();

    if (!$domain->_id) {
      return $this->_ref_group_domain;
    }

    $group_domain            = new CGroupDomain();
    $group_domain->domain_id = $domain->_id;
    $group_domain->master    = 1;
    $group_domain->loadMatchingObject();

    $this->_object_class = $group_domain->object_class;

    return $this->_ref_group_domain = $group_domain;
  }

  function loadView() {
    if (!$this->_id) {
      return;
    }

    parent::loadView();

    $this->loadMasterDomain();

    if (!$this->_object_class) {
      return;
    }

    $object      = new $this->_object_class;
    $this->_view = self::formatValue($object, $this->pattern, $this->value, $this->extra_data);
  }

  static function generateIdex(CMbObject $object, $tag, $group_id) {
    $group = CGroups::loadFromGuid("CGroups-$group_id");

    // On préfère générer un identifiant d'un établissement virtuel pour les séjours non-facturables
    $group_id_pour_sejour_facturable = CAppUI::conf('dPsante400 CDomain group_id_pour_sejour_facturable', $group);
    if ($object instanceof CSejour && !$object->facturable && $group_id_pour_sejour_facturable) {
      $group_id = $group_id_pour_sejour_facturable;
    }

    $group_domain               = new CGroupDomain();
    $group_domain->object_class = $object->_class;
    $group_domain->group_id     = $group_id;
    $group_domain->master       = 1;
    $group_domain->loadMatchingObject();
    if (!$group_domain->_id) {
      return;
    }

    $domain = $group_domain->loadRefDomain();

    $conf             = CAppUI::conf("dPsante400 CIncrementer");
    $cluster_count    = abs(intval($conf["cluster_count"]));
    $cluster_position = abs(intval($conf["cluster_position"]));

    if ($cluster_count == 0) {
      $cluster_count = 1;
    }
    if ($cluster_count == 1) {
      $cluster_position = 0;
    }

    $mutex = new CMbMutex("incrementer-$object->_class");
    $mutex->acquire(60);

    $incrementer = $domain->loadRefIncrementer();

    // Chargement du dernier 'increment' s'il existe sinon on déclenche une erreur
    if (!$incrementer->_id) {
      $mutex->release();

      return;
    }

    // Incrementation de l'idex
    $value = $incrementer->value;

    // Valeur compatible avec la position dans le cluster
    do {
      $value++;
    } while ($value % $cluster_count != $cluster_position);

    $range_min = $incrementer->range_min;
    $range_max = $incrementer->range_max;
    if ($incrementer->manage_range && $range_min !== null && $range_max !== null && CMbRange::in($value, $range_min, $range_max)) {
      $value = $range_max + 1;
    }

    do {
      // Idex vraiment disponible ?
      $idex               = new CIdSante400();
      $idex->object_class = $object->_class;
      $idex->tag          = $tag;
      $idex->id400        = self::formatValue($object, $incrementer->pattern, $value, $incrementer->extra_data);
      $idex->loadMatchingObject();
    } while ($idex->_id && ($value += $cluster_count));

    $incrementer->value = $value;
    $incrementer->store();

    // Création de l'identifiant externe
    $idex->object_id = $object->_id;
    $idex->store();

    $mutex->release();

    return $idex;
  }

  /**
   * @param CMbObject|CPatient|CSejour $object Object to build the vars array
   *
   * @return array
   */
  static function getVars(CMbObject $object) {
    $vars         = $object->getIncrementVars();
    $default_vars = array(
      "XX"   => "",
      "YYYY" => CMbDT::format(null, "%Y"),
      "YY"   => CMbDT::format(null, "%y"),
    );
    $vars         = array_merge($vars, $default_vars);

    return $vars;
  }

  static function formatValue(CMbObject $object, $pattern, $value, $extra_data) {
    $vars = self::getVars($object);

    $pattern = str_replace("[XX]", $extra_data, $pattern);

    foreach ($vars as $_var => $_value) {
      $pattern = str_replace("[$_var]", $_value, $pattern);
    }

    return sprintf($pattern, $value);
  }
}