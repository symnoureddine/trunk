<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;
use Ox\Core\CMbObject;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Mediboard\Etablissement\CGroups;

/**
 * View sender source class. 
 * @abstract Encapsulate an FTP source for view sending purposes only
 */
class CViewSenderSource extends CMbObject {
  // DB Table key
  public $source_id;
  
  // DB fields
  public $name;
  public $libelle;
  public $group_id;
  public $actif;
  public $archive;
  public $password;
  
  // Form fields
  public $_type_echange;

  /** @var CExchangeSource */
  public $_ref_source;
  
  /** @var CGroups */
  public $_ref_group;
  
  public $_reachable;
  
  /** @var CSourceToViewSender[] */
  public $_ref_senders;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "view_sender_source";
    $spec->key   = "source_id";
    $spec->uniques["name"] = array("name");
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["name"]     = "str notNull";
    $props["libelle"]  = "str";
    $props["group_id"] = "ref notNull class|CGroups autocomplete|text back|view_sender_sources";
    $props["actif"]    = "bool notNull";
    $props["archive"]  = "bool notNull";
    $props["password"]  = "str maxLength|64 show|0 loggable|0";

    $props["_reachable"] = "bool";
    return $props;
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();
    
    $this->_type_echange = $this->_class;
    $this->_view         = $this->name . ($this->libelle ? " - $this->libelle" : "");
  }

  function loadRefGroup() {
    return $this->_ref_group = $this->loadFwdRef("group_id", 1);
  }
  
  function loadRefSource() {
    return $this->_ref_source = CExchangeSource::get("$this->_guid", array(CSourceFTP::TYPE, CSourceSFTP::TYPE, CSourceFileSystem::TYPE), true, $this->_type_echange);
  }
  
  function loadRefSenders() {
    $senders_link = $this->loadBackRefs("senders_link");
    return $this->_ref_senders = CMbObject::massLoadFwdRef($senders_link, "sender_id");
  }
}
