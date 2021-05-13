<?php
/**
 * @package Mediboard\Ihe
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\handlers;

use Ox\Core\CMbException;
use Ox\Core\CStoredObject;
use Ox\Core\Module\CModule;
use Ox\Interop\Hl7\CReceiverHL7v2;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CFileTraceability;
use Ox\Mediboard\Patients\CEvenementPatient;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Class CORUDelegatedHandler
 * ORU Delegated Handler
 */
class CORUDelegatedHandler extends CHL7DelegatedHandler
{
    /** @var string[] Classes eligible for handler */
    private static $handled = ['CFile', 'CCompteRendu'];

    /** @var string Message */
    public $message = "ORU";

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

        /** @var CDocumentItem $mbObject */
        if (!$mbObject->send) {
            return false;
        }

        if ($mbObject->_no_synchro_eai) {
            return false;
        }

        // Dans le cas d'un modèle pour le compte-rendu
        if ($mbObject instanceof CCompteRendu && !$mbObject->object_id) {
            return false;
        }

        $code = 'R01';
        if (!$this->isMessageSupported($this->message, $code, $receiver)) {
            return false;
        }

        if ($receiver->_configs['files_mode_sas']) {
            return false;
        }

        $target = $mbObject->loadTargetObject();
        if (!$target || !$target->_id) {
            return false;
        }

        if (
            !$target instanceof CSejour && !$target instanceof CConsultation && !$target instanceof CPatient
            && (CModule::getActive('oxCabinet') && (!$target instanceof CEvenementPatient && !$target instanceof CPatient))
        ) {
            return false;
        }

        // On envoie le flux R01
        $this->sendEvent($this->message, $code, $mbObject);

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

        /** @var CReceiverHL7v2 $receiver */
        $receiver = $mbObject->_receiver;

        $mbObject = $mbObject->loadOldObject();
        if (!$mbObject || !$mbObject->_id) {
            return false;
        }

        $mbObject->_receiver = $receiver;

        /** @var CDocumentItem $mbObject */
        if (!$mbObject->send) {
            return false;
        }

        if ($mbObject->_no_synchro_eai) {
            return false;
        }

        // Dans le cas d'un modèle pour le compte-rendu
        if ($mbObject instanceof CCompteRendu && !$mbObject->object_id) {
            return false;
        }

        $code = 'R01';
        if (!$this->isMessageSupported($this->message, $code, $receiver)) {
            return false;
        }

        if ($receiver->_configs['files_mode_sas']) {
            return false;
        }

        $target = $mbObject->loadTargetObject();
        if (!$target || !$target->_id) {
            return false;
        }

        if (
            !$target instanceof CSejour && !$target instanceof CConsultation && !$target instanceof CPatient
            && (CModule::getActive('oxCabinet') && (!$target instanceof CEvenementPatient && !$target instanceof CPatient))
        ) {
            return false;
        }

        // On force le champ annule à 1
        $mbObject->annule = 1;

        // On envoie le flux R01
        $this->sendEvent($this->message, $code, $mbObject);

        return true;
    }
}
