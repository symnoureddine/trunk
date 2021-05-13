<?php
/**
 * @package Mediboard\cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;

use Ox\Core\CMbDT;
use Ox\Interop\Eai\CEchangeXML;
use Ox\Interop\Eai\CExchangeDataFormat;
use Ox\Interop\Eai\CInteropActor;

/**
 * Class CExchangeCDA
 * Exchange CDA
 */

class CExchangeCDA extends CEchangeXML {
  static $messages = array(
    "CDA" => "CCDAEvent"
  );

  // DB Table key
  public $exchange_cda_id;

  /**
   * @see parent::getSpec()
   */
  function getSpec() {
    $spec = parent::getSpec();
    $spec->loggable = false;
    $spec->table = 'exchange_cda';
    $spec->key   = 'exchange_cda_id';
    return $spec;
  }

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();
    $props["receiver_id"]   = "ref class|CReceiverCDA autocomplete|nom back|echanges";
    $props["initiateur_id"] = "ref class|CExchangeCDA back|notifications";
    $props["object_class"]  = "enum list|CSejour|CPatient|CConsultation|CCompteRendu|CFile show|0";
    $props["object_id"]    .= " back|exchanges_cda cascade";
    $props["group_id"]     .= " back|exchanges_cda";

    $props['sender_id'] .= ' back|exchanges_cda';
    $props['message_content_id'] .= ' back|messages_cda';
    $props['acquittement_content_id'] .= ' back|acquittements_cda';

    return $props;
  }

  /**
   * @see parent::loadRefsBack()
   */
  function loadRefsBack() {
    parent::loadRefsBack();

    $this->loadRefNotifications();
  }

  /**
   * @see parent::loadRefNotifications()
   */
  function loadRefNotifications(){
    $this->_ref_notifications = $this->loadBackRefs("notifications");
  }

  /**
   * Check if data is well formed
   *
   * @param string        $data  Data
   * @param CInteropActor $actor Actor
   *
   * @return CCDADomDocument|null
   */
  function isWellFormed($data, CInteropActor $actor = null) {
    $dom = new CCDADomDocument();
    if ($dom->loadXML($data, LIBXML_NOWARNING | LIBXML_NOERROR) !== false) {
      return $dom;
    }

    return null;
  }

  /**
   * @see parent::understand()
   */
  function understand($data, CInteropActor $actor = null) {
    if (!$dom = $this->isWellFormed($data)) {
      return false;
    }

    $root = $dom->documentElement;
    $nodeName = $root->nodeName;
    foreach ($this->getFamily() as $_message) {
      $message_class = new $_message;
      $document_elements = $message_class->getDocumentElements();

      if (array_key_exists($nodeName, $document_elements)) {
        $this->_family_message = new $document_elements[$nodeName];
        return true;
      }
    }

    return false;
  }
  /**
   * @see parent::handle()
   */
  function handle() {
    return COperatorCDA::event($this);
  }

  /**
   * @see parent::getFamily()
   */
  function getFamily() {
    return self::$messages;
  }

  /**
   * Populate exchange
   *
   * @param CExchangeDataFormat $data_format Data format
   * @param CCDAEvent           $dom_evt     Event CDA
   *
   * @return string
   */
  function populateEchange(CExchangeDataFormat $data_format, CCDAEvent $dom_evt) {
    $this->date_production = CMbDT::dateTime();
    $this->group_id        = $data_format->group_id;
    $this->sender_id       = $data_format->sender_id;
    $this->sender_class    = $data_format->sender_class;
    $this->type            = $dom_evt->type;
    $this->_message        = $data_format->_message;
  }

  /**
   * Populate error exchange
   *
   * @param string $msgAcq     Acknowledgment
   * @param bool   $doc_valid  Document is valid ?
   * @param string $type_error Error type
   *
   * @return string|void
   */
  function populateErrorEchange($msgAcq, $doc_valid, $type_error) {
    $this->_acquittement       = $msgAcq;
    $this->statut_acquittement = $type_error;
    $this->message_valide      = 0;
    $this->acquittement_valide = $doc_valid ? 1 : 0;
    $this->send_datetime       = CMbDT::dateTime();
    $this->store();
  }
}
