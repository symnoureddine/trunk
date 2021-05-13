<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Exception;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;

/**
 * Class CMessageSupported
 * Message supported
 */
class CMessageSupported extends CMbObject {
  public $message_supported_id;
  
  public $message;
  public $active;
  public $profil;
  public $transaction;

  // Meta
  public $object_id;
  public $object_class;
  public $_ref_object;

  /** @var  CExchangeDataFormat */
  public $_data_format;

  public $_event = null;

  /**
   * @see parent::getSpec
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->table = "message_supported";
    $spec->key   = "message_supported_id";
    return $spec;
  }

  /**
   * @see parent::getProps
   */
  function getProps() {
    $props = parent::getProps();

    $props["object_id"]    = "ref notNull class|CInteropActor meta|object_class cascade back|messages_supported";
    $props["object_class"] = "str notNull show|0";
    $props["message"]      = "str notNull";
    $props["active"]       = "bool default|0";
    $props["profil"]       = "str";
    $props["transaction"]  = "str";
    
    return $props;
  }

  /**
   * Load event by name
   *
   * @return mixed
   */
  function loadEventByName() {
    $classname = $this->message;

    if (preg_match_all('/ADT|QBP|ORU|QCN|QBP|ORM|SIU|MFN/', $classname, $matches)) {
      $classname = str_replace("CHL7Event", "CHL7v2Event", $classname);
    }

    return $this->_event = new $classname;
  }


  /**
   * @param CStoredObject $object
   * @deprecated
   * @todo redefine meta raf
   * @return void
   */
  public function setObject(CStoredObject $object) {
    CMbMetaObjectPolyfill::setObject($this, $object);
  }

  /**
   * @param bool $cache
   * @deprecated
   * @todo redefine meta raf
   * @return mixed
   * @throws Exception
   */
  public function loadTargetObject($cache = true) {
    return CMbMetaObjectPolyfill::loadTargetObject($this, $cache);
  }

  /**
   * @inheritDoc
   * @todo remove
   */
  function loadRefsFwd() {
    parent::loadRefsFwd();
    $this->loadTargetObject();
  }
}
