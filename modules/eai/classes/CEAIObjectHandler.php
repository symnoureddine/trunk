<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CClassMap;
use Ox\Core\CMbObject;
use Ox\Core\Handlers\ObjectHandler;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Patients\CPatient;

/**
 * Class CEAIObjectHandler
 * EAI Object handler
 */
class CEAIObjectHandler extends ObjectHandler
{
    /** @var array */
    public static $handled = [];

    /** @var  string Sender GUID */
    public $_eai_sender_guid;

    /**
     * @inheritdoc
     */
    public static function isHandled(CStoredObject $object)
    {
        return !$object->_ignore_eai_handlers && in_array($object->_class, self::$handled);
    }

    /**
     * Trigger action on the right handler
     *
     * @param string        $action Action name
     * @param CStoredObject $object Object
     *
     * @return void
     * @throws Exception
     */
    public function sendFormatAction($action, CStoredObject $object)
    {
        if (!$action) {
            return;
        }

        $cn_receiver_guid = CValue::sessionAbs("cn_receiver_guid");

        $receiver = new CInteropReceiver();
        // Parcours des receivers actifs
        if (!$cn_receiver_guid) {
            // On est dans le cas d'un store d'un objet depuis MB
            if (!$object->_eai_sender_guid) {
                $receivers = $receiver->getObjects(true);
            }
            // On est dans le cas d'un enregistrement provenant d'une interface
            else {
                $receivers = [];

                /** @var CInteropSender $sender */
                $sender = CMbObject::loadFromGuid($object->_eai_sender_guid);

                // On utilise le routeur de l'EAI
                if (CAppUI::conf("eai use_routers")) {
                    // Récupération des receivers de ttes les routes actives
                    /** @var CEAIRoute[] $routes */
                    $where           = [];
                    $where["active"] = " = '1'";
                    $routes          = $sender->loadBackRefs(
                        "routes_sender",
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $where
                    );

                    foreach ($routes as $_route) {
                        if (!$_route->active) {
                            continue;
                        }

                        $receiver                                 = $_route->loadRefReceiver();
                        $receivers[CClassMap::getSN($receiver)][] = $receiver;
                    }
                }
                // On ne va transmettre aucun message, hormis pour les patients
                else {
                    if (!$object instanceof CPatient) {
                        return;
                    }

                    $no_group = null;
                    // Dans le cas des patients on va envoyer à tous les destinataires

                    // On ne transmet pas le message aux destinataires du même établissement que celui de l'expéditeur
                    if (!CAppUI::conf("eai send_messages_with_same_group")) {
                        $no_group = $sender->group_id;
                    }

                    /* @todo ATTENTION PROBLEMATIQUE SI PATIENT != CASSE */
                    //$receivers = $receiver->getObjects(true, $no_group);
                }
            }
        }
        // Sinon envoi destinataire sélectionné (cas sur un destinataire ciblé ex. mod-connectathon)
        else {
            if ($cn_receiver_guid === "none") {
                return;
            }
            $receiver = CMbObject::loadFromGuid($cn_receiver_guid);
            if (!$receiver || !$receiver->_id) {
                return;
            }
            $receivers[$receiver->_class][] = $receiver;
        }

        foreach ($receivers as $_receivers) {
            if (!$_receivers) {
                continue;
            }
            /** @var CInteropReceiver $_receiver */
            foreach ($_receivers as $_receiver) {
                // Destinataire non actif on envoi pas
                if (!$_receiver->actif) {
                    continue;
                }

                // Receiver use specific handler
                if ($_receiver->use_specific_handler) {
                    continue;
                }

                $handler = $_receiver->getFormatObjectHandler($this);
                if (!$handler) {
                    continue;
                }

                $_receiver->loadConfigValues();
                $_receiver->loadRefsMessagesSupported();

                // Affectation du receiver à l'objet
                $object->_receiver = $_receiver;

                $handlers = !is_array($handler) ? [$handler] : $handler;

                // On parcours les handlers
                foreach ($handlers as $_handler) {
                    // Récupère le handler du format
                    $format_object_handler = new $_handler();

                    // Envoi l'action au handler du format
                    try {
                        // Receiver use specific handler
                        if ($_receiver->use_specific_handler) {
                            continue;
                        }

                        // Method may not have been implemented
                        if (is_callable([$format_object_handler, $action])) {
                            $format_object_handler->$action($object);
                        }
                    } catch (Exception $e) {
                        CAppUI::setMsg($e->getMessage(), UI_MSG_WARNING);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function onBeforeStore(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (isset($object->_eai_sender_guid)) {
            $this->_eai_sender_guid = $object->_eai_sender_guid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onAfterStore(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (!$object->_ref_last_log && $object->_class !== "CIdSante400") {
            return false;
        }

        // Cas d'une fusion
        if ($object->_merging) {
            return false;
        }

        if ($object->_forwardRefMerging) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onBeforeMerge(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (!$object->_merging) {
            return false;
        }

        if (isset($object->_eai_sender_guid)) {
            $this->_eai_sender_guid = $object->_eai_sender_guid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onMergeFailure(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (isset($object->_fusion) && !$object->_fusion) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onAfterMerge(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (!$object->_merging) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onBeforeDelete(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        if (isset($object->_eai_sender_guid)) {
            $this->_eai_sender_guid = $object->_eai_sender_guid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function onAfterDelete(CStoredObject $object)
    {
        if (!$this->isHandled($object)) {
            return false;
        }

        return true;
    }
}
