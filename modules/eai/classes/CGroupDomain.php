<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

/**
 * Class CGroupDomain
 * Identification group domain
 */

use Ox\Core\CMbObject;
use Ox\Mediboard\Etablissement\CGroups;

class CGroupDomain extends CMbObject {
  // DB Table key
  public $group_domain_id;
  
  // DB fields
  public $object_class;
  public $group_id;
  public $domain_id;
  public $master;

  /** @var CGroups */
  public $_ref_group; 

  /** @var CDomain */
  public $_ref_domain;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = 'group_domain';
    $spec->key   = 'group_domain_id';
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    
    $props["object_class"] = "enum notNull list|CPatient|CSejour|CMbObject";
    $props["group_id"]     = "ref notNull class|CGroups autocomplete|text back|group_domains";
    $props["domain_id"]    = "ref notNull class|CDomain back|group_domains";
    $props["master"]       = "bool notNull";

    return $props;
  }

  /**
   * @inheritdoc
   */
  function check() {
    parent::check();
    
    $this->completeField("domain_id", "object_class", "group_id");

    // Recherche si on a pas déjà un établissement du domaine pour un type d'objet différent
    $ljoin = array(
      "domain" => "domain.domain_id = group_domain.domain_id"
    );

    $group_domain = new CGroupDomain();

    // Recherche si on a un établissement du domaine déjà en master avec le même type d'objet et le même établissement
    if ($this->master) {
      $where = array(
        "master"        => " = '1'",
        "object_class"  => " = '$this->object_class'",
        "group_id"      => " = '$this->group_id'",
        "domain.active" => " = '1'",
      );

      if ($group_domain->countList($where, null, $ljoin) > 0) {
        return "CGroupDomain-master_already_exist";
      }
    }

    $where = array(
      "domain.domain_id" => " = '$this->domain_id'",
      "incrementer_id"   => "IS NOT NULL",
      "object_class"     => " != '$this->object_class'",
      "domain.active"    => " = '1'"
    );

    if ($group_domain->countList($where, null, $ljoin) > 0) {
      return "CGroupDomain-object_class_already_exist";
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  function merge($objects, $fast = false) {
    return "CGroupDomain-merge_impossible";
  }

  /**
   * Load group
   *
   * @return CGroups
   */
  function loadRefGroup() {
    if ($this->_ref_group) {
      return $this->_ref_group;
    }

    return $this->_ref_group = $this->loadFwdRef("group_id", 1);
  }

  /**
   * Load domain
   *
   * @return CDomain
   */
  function loadRefDomain() {
    if ($this->_ref_domain) {
      return $this->_ref_domain;
    }

    return $this->_ref_domain = $this->loadFwdRef("domain_id", 1);
  }

  /**
   * Load groups domain
   *
   * @param string $domain_type Object class
   *
   * @return self[]
   */
  static function loadGroupsDomain($domain_type) {
    $group_domain  = new self();
    $group_domain->object_class = $domain_type;
    /** @var CGroupDomain[] $group_domains */
    $group_domains = $group_domain->loadMatchingList();

    foreach ($group_domains as $_group_domain) {
      $_group_domain->loadRefDomain();
    }

    return $group_domains;
  }

  /**
   * Is master IPP ?
   *
   * @return bool
   */
  function isMasterIPP() {
    return $this->master && $this->loadRefDomain()->active && ($this->object_class === "CPatient");
  }

  /**
   * Is master NDA ?
   *
   * @return bool
   */
  function isMasterNDA() {
    return $this->master && $this->loadRefDomain()->active && ($this->object_class === "CSejour");
  }
} 