<?php
/**
 * @package Mediboard\Ihe
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\handlers;

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CStoredObject;
use Ox\Interop\Hl7\CReceiverHL7v2;
use Ox\Mediboard\Patients\CAntecedent;

/**
 * Class CADTDelegatedHandler
 * ADT Delegated Handler
 */
class CADTDelegatedHandler extends CHL7DelegatedHandler
{
    /** @var string[] Classes eligible for handler */
    private static $handled = ["CAntecedent"];

    /** @var string Message */
    public $message = "ADT";

    /**
     * If object is handled ?
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public static function isHandled(CStoredObject $mbObject): bool
    {
        return in_array($mbObject->_class, self::$handled);
    }

    /**
     * Trigger after event store
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public function onBeforeStore(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        return true;
    }

    /**
     * Trigger after event store
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     * @throws CMbException
     */
    public function onAfterStore(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        /** @var CReceiverHL7v2 $receiver */
        $receiver = $mbObject->_receiver;

        // Gestion des allergies - A60
        if ($mbObject instanceof CAntecedent) {
            if (
                ($receiver->_configs['HL7_version'] && $receiver->_configs['HL7_version'] < "2.7") ||
                !$receiver->_configs['HL7_version'] && (CAppUI::conf("hl7 default_version") < "2.7")
            ) {
                return false;
            }

            $antecedent            = $mbObject;
            $antecedent->_receiver = $receiver;

            $types_antecedents_adt_a60     = explode("|", CAppUI::conf("hl7 type_antecedents_adt_a60"));
            $appareils_antecedents_adt_a60 = explode("|", CAppUI::conf("hl7 appareil_antecedents_adt_a60"));
            if (
                !CMbArray::in($antecedent->type, $types_antecedents_adt_a60) && !CMbArray::in(
                    $antecedent->appareil,
                    $appareils_antecedents_adt_a60
                )
            ) {
                return false;
            }

            $dossier_medical = $antecedent->loadRefDossierMedical();
            if ($dossier_medical->object_class != "CPatient") {
                return false;
            }

            $dossier_medical->loadRefObject();
            $code = "A60";
            if (!$this->isMessageSupported($this->message, $code, $receiver)) {
                return false;
            }

            $this->sendEvent($this->message, $code, $antecedent);
        }

        return true;
    }

    /**
     * Trigger before event merge
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public function onBeforeMerge(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        return true;
    }

    /**
     * Trigger when merge failed
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public function onMergeFailure(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        return true;
    }

    /**
     * Trigger after event merge
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public function onAfterMerge(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        return true;
    }

    /**
     * Trigger before event delete
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     * @throws CMbException
     */
    public function onBeforeDelete(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        /** @var CReceiverHL7v2 $receiver */
        $receiver = $mbObject->_receiver;

        // Gestion des allergies - A60
        if ($mbObject instanceof CAntecedent) {
            if (
                ($receiver->_configs['HL7_version'] && $receiver->_configs['HL7_version'] < "2.7") ||
                !$receiver->_configs['HL7_version'] && (CAppUI::conf("hl7 default_version") < "2.7")
            ) {
                return false;
            }

            $antecedent            = $mbObject;
            $antecedent->_receiver = $receiver;

            $types_antecedents_adt_a60     = explode("|", CAppUI::conf("hl7 type_antecedents_adt_a60"));
            $appareils_antecedents_adt_a60 = explode("|", CAppUI::conf("hl7 appareil_antecedents_adt_a60"));
            if (
                !CMbArray::in($antecedent->type, $types_antecedents_adt_a60) && !CMbArray::in(
                    $antecedent->appareil,
                    $appareils_antecedents_adt_a60
                )
            ) {
                return false;
            }

            $dossier_medical = $antecedent->loadRefDossierMedical();
            if ($dossier_medical->object_class != "CPatient") {
                return false;
            }

            $dossier_medical->loadRefObject();
            $code = "A60";
            if (!$this->isMessageSupported($this->message, $code, $receiver)) {
                return false;
            }

            $antecedent->_delete = true;

            $this->sendEvent($this->message, $code, $antecedent);
        }

        return true;
    }

    /**
     * Trigger after event delete
     *
     * @param CStoredObject $mbObject Object
     *
     * @return bool
     */
    public function onAfterDelete(CStoredObject $mbObject): bool
    {
        if (!$this->isHandled($mbObject)) {
            return false;
        }

        return true;
    }
}
