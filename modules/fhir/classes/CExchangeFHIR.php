<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir;
use Ox\Core\CMbFieldSpecFact;
use Ox\Interop\Eai\CExchangeBinary;
use Ox\Interop\Eai\CInteropActor;
use Ox\Mediboard\System\CContentAny;

/**
 * Class CExchangeFHIR
 * Exchange FHIR
 */
class CExchangeFHIR extends CExchangeBinary {
  /** @var array */
  static $messages = array(
    "PDQm" => "CPDQm",
    "PIXm" => "CPIXm",
    "MHD"  => "CMHD",
    "FHIR" => "CFHIR",
  );

  /** @var string */
  public $exchange_fhir_id;

  /** @var string */
  public $format;

  public $_exchange_fhir;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->loggable = false;
    $spec->table = 'exchange_fhir';
    $spec->key   = 'exchange_fhir_id';
    
    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["group_id"]               .= " back|echanges_fhir";
    $props["sender_class"]            = "enum list|CSenderSOAP|CSenderHTTP show|0";
    $props["sender_id"]              .= " back|expediteur_fhir";
    $props["message_content_id"]      = "ref class|CContentAny show|0 cascade back|messages_fhir";
    $props["acquittement_content_id"] = "ref class|CContentAny show|0 cascade back|acquittements_fhir";
    $props["receiver_id"]             = "ref class|CReceiverFHIR autocomplete|nom back|echanges";
    $props["object_class"]            = "str class show|0";
    $props["object_id"]              .= " back|exchanges_fhir";
    $props["format"]                  = "str";

    $props["_message"]                = "str";
    $props["_acquittement"]           = "str";

    return $props;
  }

  /**
   * Handle exchange
   *
   * @return null|string|void
   */
  function handle() {
  }

  /**
   * Get exchange FHIR families
   *
   * @return array Families
   */
  function getFamily() {
    return self::$messages;
  }

  /**
   * Check if data is well formed
   *
   * @param string        $data  Data
   * @param CInteropActor $actor Actor
   *
   * @return bool
   */
  function isWellFormed($data, CInteropActor $actor = null) {
    return false;
  }

  /**
   * Check if data is understood
   *
   * @param string        $data  Data
   * @param CInteropActor $actor Actor
   *
   * @return bool
   */
  function understand($data, CInteropActor $actor = null) {
    return false;
  }

  /**
   * Get exchange errors
   *
   * @return bool|void
   */
  function getErrors() {
  }

  /**
   * @see parent::loadContent()
   */
  function loadContent() {
    $this->_ref_message_content = $this->loadFwdRef("message_content_id", true);
    $this->_message = $this->_ref_message_content->content;

    $this->_ref_acquittement_content = $this->loadFwdRef("acquittement_content_id", true);
    $this->_acquittement = $this->_ref_acquittement_content->content;
  }

  /**
   * @see parent::guessDataType()
   */
  function guessDataType(){
    $data_types = array(
      "<?xml" => "xml",
      "{"     => "json",
    );

    foreach ($data_types as $check => $spec) {
      if (strpos($this->_message, $check) === 0) {
        $this->_props["_message"] = $spec;
        $this->_specs["_message"] = CMbFieldSpecFact::getSpec($this, "_message", $spec);
      }

      if (strpos($this->_acquittement, $check) === 0) {
        $this->_props["_acquittement"] = $spec;
        $this->_specs["_acquittement"] = CMbFieldSpecFact::getSpec($this, "_acquittement", $spec);
      }
    }
  }

  /**
   * @inheritdoc
   */
  function updatePlainFields() {
    if ($this->_message !== null) {
      /** @var CContentAny $content */
      $content = $this->loadFwdRef("message_content_id", true);
      $content->content = $this->_message;
      if ($msg = $content->store()) {
        return $msg;
      }
      if (!$this->message_content_id) {
        $this->message_content_id = $content->_id;
      }
    }

    if ($this->_acquittement !== null) {
      /** @var CContentAny $content */
      $content = $this->loadFwdRef("acquittement_content_id", true);
      $content->content = $this->_acquittement;
      if ($msg = $content->store()) {
        return $msg;
      }
      if (!$this->acquittement_content_id) {
        $this->acquittement_content_id = $content->_id;
      }
    }
  }
}