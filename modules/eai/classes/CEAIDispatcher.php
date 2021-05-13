<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbXMLDocument;
use Ox\Interop\Hl7\CExchangeHL7v2;
use Ox\Interop\Hl7\CHL7v2TableEntry;
use Ox\Interop\Hprimxml\CEchangeHprim;
use Ox\Mediboard\System\CSourceFileSystem;

/**
 * Class CEAIDispatcher
 * Dispatcher EAI
 */
class CEAIDispatcher implements IShortNameAutoloadable
{
    /**
     * Error logs
     */
    static $errors = [];

    /**
     * MB XML errors
     */
    static $error = null;

    /** @var array Allowed classes */
    static $allowed_classes = ["CHL7EventACK"];

    /**
     * Dispatch message
     *
     * @param string         $data         Data
     * @param CInteropSender $actor        Actor data
     * @param int            $exchange_id  Identifier exchange
     * @param bool           $to_treatment Treat the exchange
     *
     * @return string Dispatch response
     * @throws Exception
     */
    static function dispatch($data, CInteropSender $actor = null, $exchange_id = null, $to_treatment = true)
    {
        $contexts = null;
        // Dicom a besoin des contextes de présentation afin de pouvoir déchiffrer le message
        if (is_array($data)) {
            $contexts = $data["pres_contexts"];
            $data     = $data["msg"];
        }

        if (substr($data, 0, 3) === "\xEF\xBB\xBF") {
            $data = substr($data, 3);
        }

        if ($actor && isset($actor->_configs["encoding"]) && $actor->_configs["encoding"] == "UTF-8") {
            $data = utf8_decode($data);
        }

        self::$errors = [];
        // Accepte t-on des utilisateurs acteurs non enregistrés ?
        if (!$actor) {
            CEAIDispatcher::$errors[] = CAppUI::tr("CEAIDispatcher-no_actor");

            return self::dispatchError($data, $actor);
        }

        // est-ce que je comprend la famille de messages ?
        /** @var CExchangeDataFormat $data_format */
        if (($data_format = self::understand($data, $actor, $contexts)) === null) {
            self::$errors[] = CAppUI::tr("CEAIDispatcher-no_understand");

            return self::dispatchError($data, $actor);
        }

        $actor->_data_format = $data_format;

        $data_format->sender_id     = $actor->_id;
        $data_format->sender_class  = $actor->_class;
        $data_format->group_id      = $actor->group_id;
        $data_format->_ref_sender   = $actor;
        $data_format->_message      = $data;
        $data_format->_exchange_id  = $exchange_id;
        $data_format->_to_treatment = $to_treatment;

        // est-ce que je supporte cette famille de messages ?
        $supported            = false;
        $family_message_class = (!$data_format->_family_message_class) ?
            CClassMap::getSN($data_format->_family_message) : $data_format->_family_message_class;

        $msg_supported_classes = $data_format->getMessagesSupported($actor->_guid, false, null, true);

        if (CAppUI::conf("eai message_supported")) {
            if (in_array($data_format->_family_message_class, self::$allowed_classes)) {
                $supported = true;
            }

            foreach ($msg_supported_classes as $_msg_supported_class => $_msg_supported) {
                foreach ($_msg_supported as $_msg) {
                    $classes = class_implements($data_format->_family_message);
                    if (array_key_exists($_msg->message, CClassMap::getSN($classes))) {
                        $supported = true;
                    } else {
                        self::$errors[] = CAppUI::tr(
                            "CEAIDispatcher-_message_no_supported_for_this_actor",
                            $data_format->_family_message
                        );
                    }
                }
            }
        } else {
            if (in_array($data_format->_family_message_class, self::$allowed_classes)) {
                $supported = true;
            }

            foreach ($msg_supported_classes as $_msg_supported_class => $_msg_supported) {
                if ($family_message_class == $_msg_supported_class) {
                    $supported = true;
                }
            }
        }

        if (!$supported) {
            self::$errors[] = CAppUI::tr(
                "CEAIDispatcher-_family_message_no_supported_for_this_actor",
                $family_message_class
            );

            return self::dispatchError($data, $actor, $data_format);
        }

        // Traitement par le handler du format
        try {
            return $data_format->handle();
        } catch (CMbException $e) {
            self::$errors[] = $e->getMessage();

            return self::dispatchError($data, $actor, $data_format);
        }
    }

    /**
     * Dispatch error
     *
     * @param string              $data        Data
     * @param CInteropSender      $actor       Actor data
     * @param CExchangeDataFormat $data_format Data format
     *
     * @return bool Always false
     * @throws Exception
     */
    static function dispatchError($data, $actor = null, CExchangeDataFormat $data_format = null)
    {
        foreach (self::$errors as $_error) {
            CAppUI::stepAjax($_error, UI_MSG_WARNING);
        }

        // Création d'un échange Any
        $exchange_any                  = new CExchangeAny();
        $exchange_any->date_production = CMbDT::dateTime();
        if ($actor) {
            $exchange_any->sender_id    = $actor->_id;
            $exchange_any->sender_class = $actor->_class;
            $exchange_any->group_id     = $actor->group_id;
        }
        $exchange_any->type     = "None";
        $exchange_any->_message = $data;
        $exchange_any->store();

        self::createACK($data_format);

        return false;
    }

    /**
     * Create acknowledgment
     *
     * @param CExchangeDataFormat $data_format Data format
     *
     * @return bool Always false
     * @throws Exception
     */
    static function createACK(CExchangeDataFormat $data_format = null)
    {
        if (!$data_format) {
            return self::mbDispatchErrors();
        }

        $comments = null;
        foreach (self::$errors as $_error) {
            $comments .= "$_error";
        }

        switch ($data_format->_class) {
            case "CExchangeHL7v2":
                /** @var CExchangeHL7v2 $data_format */
                $data_format->load($data_format->_exchange_id);

                $sender = $data_format->_ref_sender;

                $sender->getConfigs($data_format);
                $configs = $sender->_configs;

                $now         = CMbDT::format(null, "%Y%m%d%H%M%S");
                $sending_app = CAppUI::conf("hl7 CHL7 sending_application", "CGroups-$sender->group_id");
                $sending_fac = CAppUI::conf("hl7 CHL7 sending_facility", "CGroups-$sender->group_id");

                $recv_app = isset($configs["receiving_application"]) ? $configs["receiving_application"] : $sender->nom;
                $recv_fac = isset($configs["receiving_facility"]) ? $configs["receiving_facility"] : $sender->nom;

                $ack = "MSH|^~\&|$sending_app|$sending_fac|$recv_app|$recv_fac|$now||ACK^R01^ACK|$now|P|2.6||||||" .
                    CHL7v2TableEntry::mapTo("211", CApp::$encoding);
                $ack .= "\r\n" . "MSA|CR|$now";
                $ack .= "\r\n" . "ERR||0^0|207|E|E000^" . $comments . "|||||||";

                self::$error = $ack;

                $data_format->statut_acquittement = "AR";
                $data_format->acquittement_valide = 1;
                $data_format->_acquittement       = $ack;
                $data_format->send_datetime       = CMbDT::dateTime();
                $data_format->response_datetime   = CMbDT::dateTime();
                $data_format->store();

                break;

            case "CEchangeHprim":
                /** @var CEchangeHprim $data_format */

                break;

            default:
                // Création d'un message de retour "MB" en XML
                self::mbDispatchErrors();
        }

        return self::$error;
    }

    /**
     * Creating an "MB" return message in XML
     *
     * @return string
     */
    static function mbDispatchErrors()
    {
        $dom       = new CMbXMLDocument();
        $mb_errors = $dom->addElement($dom, "MB_Dispatch_Errors");
        foreach (self::$errors as $_error) {
            $dom->addElement($mb_errors, "MB_Dispatch_Error", $_error);
        }

        return self::$error = $dom->saveXML();
    }

    /**
     * Message understood ?
     *
     * @param string         $data     Data
     * @param CInteropSender $actor    Actor data
     * @param mixed          $contexts Used with Dicom, the presentation contexts
     *
     * @return CExchangeDataFormat|bool Understood ?
     * @throws Exception
     */
    static function understand($data, $actor = null, $contexts = null)
    {
        foreach (CExchangeDataFormat::getAll(CExchangeDataFormat::class, false) as $_exchange_class) {
            foreach (CApp::getChildClasses($_exchange_class, true, true) as $_data_format) {
                /**
                 * @var CExchangeDataFormat $data_format
                 */
                $data_format = new $_data_format;

                // Test si le message est compris
                if ($contexts) {
                    $understand = $data_format->understand($data, $actor, $contexts);
                } else {
                    $understand = $data_format->understand($data, $actor);
                }
                if ($understand) {
                    return $data_format;
                }
            }
        }

        return null;
    }

    /**
     * Create ACK
     *
     * @param string              $msg         Data
     * @param CInteropSender      $sender      Actor data
     * @param CExchangeDataFormat $data_format Data format
     *
     * @return void
     * @throws CMbException
     */
    static function createFileACK($msg, $sender, CExchangeDataFormat $data_format = null)
    {
        if ($sender->response == "none") {
            return;
        }

        $source = reset($sender->_ref_exchanges_sources);

        $filename_ack = "MB_ACK_";
        $filename_ack .= $source->_receive_filename ? $source->_receive_filename : CSourceFileSystem::generateFileName(
        );

        $source->setData($msg);
        if ($source->ack_prefix) {
            $filename_ack = "$source->ack_prefix/$filename_ack";
        }

        $source->send($filename_ack);
    }
}
