<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;

use Exception;
use Ox\AppFine\Client\CAppFineClient;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CStoredObject;
use Ox\Core\Module\CModule;
use Ox\Interop\Eai\CEAIObjectHandler;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CInteropReceiverFactory;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Interop\Ihe\CIHE;
use Ox\Interop\Webservices\CSourceSOAP;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceFileSystem;
use Ox\Mediboard\System\CSourceHTTP;

/**
 * Class CReceiverHL7v2
 * Receiver HL7v2
 */
class CReceiverHL7v2 extends CInteropReceiver
{
    /** @var array Sources supportées par un destinataire */
    public static $supported_sources = [
        CSourceMLLP::TYPE,
        CSourceFTP::TYPE,
        CSourceSFTP::TYPE,
        CSourceSOAP::TYPE,
        CSourceHTTP::TYPE,
        CSourceFileSystem::TYPE,
    ];

    // DB Table key
    /** @var null */
    public $receiver_hl7v2_id;

    /** @var null */
    public $_extension;

    /** @var null */
    public $_i18n_code;

    /** @var null */
    public $_tag_hl7;

    /**
     * Get all receivers
     *
     * @param array $events_name
     * @param null  $group_id
     *
     * @return CStoredObject[]|CInteropReceiver[]
     * @throws Exception
     */
    public static function getReceivers($events_name = [], $group_id = null)
    {
        $receiver        = new self();
        $receiver->role  = CAppUI::conf("instance_role");
        $receiver->actif = "1";
        $receivers       = $receiver->loadMatchingList();

        $group_id = $group_id ? $group_id : CGroups::loadCurrent()->_id;

        /** @var CReceiverHL7v2 $_receiver */
        foreach ($receivers as $_receiver) {
            if ($_receiver->group_id != $group_id) {
                unset($receivers[$_receiver->_guid]);
                continue;
            }

            $objects = CInteropReceiver::getObjectsBySupportedEvents($events_name, $_receiver, true);
            foreach ($events_name as $_event_name) {
                if (!array_key_exists($_event_name, $objects)) {
                    unset($receivers[$_receiver->_guid]);
                    continue;
                }

                if (!$objects[$_event_name]) {
                    unset($receivers[$_receiver->_guid]);
                    continue;
                }
            }
        }

        return $receivers;
    }

    /**
     * @inheritDoc
     */
    public function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = 'receiver_hl7v2';
        $spec->key      = 'receiver_hl7v2_id';
        $spec->messages = [
            // HL7
            "MFN"     => ["CHL7MFN"],
            "ORU"     => ["CHL7ORU"],
            "ADT"     => ["CHL7ADT"],

            // IHE
            "PAM"     => ["evenementsPatient"],
            "PAM_FRA" => ["evenementsPatient"],
            "DEC"     => ["CDEC"],
            "SWF"     => ["CSWF"],
            "PDQ"     => ["CPDQ"],
            "PIX"     => ["CPIX"],
            "SINR"    => ["CSINR"],
            //"LTW"     => array ("CLTW"),
        ];

        return $spec;
    }

    /**
     * @inheritDoc
     */
    public function getProps()
    {
        $props = parent::getProps();

        $props["type"]     = "enum list|AppFine|Doctolib|Galaxie";
        $props["group_id"] .= " back|receivers_hl7v2";

        $props["_tag_hl7"] = "str";

        return $props;
    }

    /**
     * @inheritDoc
     */
    public function updateFormFields()
    {
        parent::updateFormFields();

        $this->_tag_hl7 = CHL7::getObjectTag($this->group_id);

        if (!$this->_configs) {
            $this->loadConfigValues();
        }
    }

    /**
     * @inheritDoc
     */
    public function check()
    {
        $this->completeField('group_id', 'actif', 'type');

        if ($this->type) {
            CInteropReceiverFactory::makeHL7v2($this->type)->checkDuplicate($this);
        }
    }

    /**
     * Checks whether the doctolib recipient is unique
     *
     * @param CReceiverHL7v2 $receiver_Hl7v2 Receiver HL7v2 create/modify
     *
     * @return bool
     * @throws Exception
     */
    public function checkDuplicate(CReceiverHL7v2 $receiver_Hl7v2): bool
    {
        return true;
    }

    /**
     * Get object handler
     *
     * @param CEAIObjectHandler $objectHandler Object handler
     *
     * @return mixed
     * @throws Exception
     */
    public function getFormatObjectHandler(CEAIObjectHandler $objectHandler)
    {
        $handlers     = CIHE::getObjectHandlers();
        $hl7_handlers = CHL7::getObjectHandlers();

        foreach ($hl7_handlers as $_handler_key => $_handler_class) {
            if (CMbArray::get($handlers, $_handler_key)) {
                if (is_array($handlers[$_handler_key])) {
                    $handlers[$_handler_key] = array_merge(
                        $handlers[$_handler_key],
                        is_array($_handler_class) ? $_handler_class : [$_handler_class]
                    );
                } else {
                    $handlers[$_handler_key] = array_merge(
                        [$handlers[$_handler_key]],
                        is_array($_handler_class) ? $_handler_class : [$_handler_class]
                    );
                }
            } else {
                $handlers[$_handler_key] = $_handler_class;
            }
        }

        $object_handler_class = CClassMap::getSN($objectHandler);
        if (array_key_exists($object_handler_class, $handlers)) {
            return $handlers[$object_handler_class];
        }
    }

    /**
     * Get HL7 version for one transaction
     *
     * @param string $transaction Transaction name
     *
     * @return null|string
     */
    public function getHL7Version(string $transaction): ?string
    {
        $iti_hl7_version = $this->_configs[$transaction . "_HL7_version"];

        foreach (CHL7::$versions as $_version => $_sub_versions) {
            if (in_array($iti_hl7_version, $_sub_versions)) {
                return $_version;
            }
        }

        return null;
    }

    /**
     * Get internationalization code
     *
     * @param string $transaction Transaction name
     *
     * @return null
     */
    public function getInternationalizationCode($transaction)
    {
        $iti_hl7_version = $this->_configs[$transaction . "_HL7_version"];
        if (preg_match("/([A-Z]{3})_(.*)/", $iti_hl7_version, $matches)) {
            $this->_i18n_code = $matches[1];
        }

        return $this->_i18n_code;
    }

    /**
     * @inheritdoc
     */
    public function sendEvent($evenement, $object, $data = [], $headers = [], $message_return = false, $soapVar = false)
    {
        if (!parent::sendEvent($evenement, $object, $data, $headers, $message_return, $soapVar)) {
            return null;
        }

        $evenement->_receiver = $this;

        // build_mode = Mode simplifié lors de la génération du message
        $this->loadConfigValues();
        CHL7v2Message::setBuildMode($this->_configs["build_mode"]);
        $evenement->build($object);
        CHL7v2Message::resetBuildMode();
        if (!$msg = $evenement->flatten()) {
            return null;
        }

        $exchange = $evenement->_exchange_hl7v2;

        // Si l'échange est invalide
        if (!$exchange->message_valide) {
            return null;
        }

        // Si on n'est pas en synchrone
        if (!$this->synchronous) {
            return null;
        }

        // Si on n'a pas d'IPP et NDA
        if ($exchange->master_idex_missing) {
            return null;
        }

        $evt    = $this->getEventMessage($evenement->profil);
        $source = CExchangeSource::get("$this->_guid-$evt");

        if (!$source->_id || !$source->active) {
            return null;
        }

        // Application des règles de transformation
        $msg = $evenement->applyRules($msg, $this);
        if (isset($evenement->altered_content_message_id) && $evenement->altered_content_message_id) {
            $exchange->altered_content_id = $evenement->altered_content_message_id;
        }

        if ($this->_configs["encoding"] == "UTF-8") {
            $msg = utf8_encode($msg);
        }

        $exchange->send_datetime = CMbDT::dateTime();

        $source->setData($msg, null, $exchange);
        try {
            $source->send();
        } catch (Exception $e) {
            throw new CMbException("CExchangeSource-no-response %s", $this->nom);
        }

        $exchange->response_datetime = CMbDT::dateTime();

        $ack_data = $source->getACQ();

        if (!$ack_data) {
            $exchange->store();

            return null;
        }

        $data_format = CIHE::getEvent($exchange);

        $ack = new CHL7v2Acknowledgment($data_format);
        $ack->handle($ack_data);
        $exchange->statut_acquittement = $ack->getStatutAcknowledgment();
        $exchange->acquittement_valide = $ack->message->isOK(CHL7v2Error::E_ERROR) ? 1 : 0;
        $exchange->_acquittement       = $ack_data;
        $exchange->store();

        if (CModule::getActive("appFineClient") && $this->_configs["send_evenement_to_mbdmp"]) {
            CAppFineClient::generateIdexEventId($this, $object, $ack_data);
        }

        return $ack_data;
    }

    /**
     * Get event message
     *
     * @param string $profil Profil name
     *
     * @return mixed
     */
    public function getEventMessage($profil)
    {
        if (!array_key_exists($profil, $this->_spec->messages)) {
            return null;
        }

        return reset($this->_spec->messages[$profil]);
    }
}
