<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Handlers\ObjectHandler;
use Ox\Core\CStoredObject;
use Ox\Core\Module\CModule;
use Ox\Interop\Dmp\CDMP;
use Ox\Interop\Dmp\CDMPSas;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Domain handler
 */
class CFileTraceabilityHandler extends ObjectHandler {
  static $handled = array("CFile", "CCompteRendu");
  public $create = false;

  /**
   * @inheritdoc
   */
  static function isHandled(CStoredObject $object) {
    return in_array($object->_class, self::$handled);
  }


  /**
   * @inheritdoc
   */
  function onBeforeStore(CStoredObject $object) {
    if (!$this->isHandled($object)) {
      return false;
    }

    if (!$object->_id) {
      $this->create = true;
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

        /** @var CDocumentItem $docItem */
        $docItem = $object;

        // Si on vient de retirer le type doc dmp => on enlève les traces
        if (CModule::getActive('dmp')) {
            $old_object = $docItem->loadOldObject();
            if ($old_object && $old_object->_id && $old_object->type_doc_dmp && !$docItem->type_doc_dmp) {
                CFileTraceability::deleteTrace($docItem, CDMPSas::getTag());
            }
        }

        if ($docItem->annule) {
            CFileTraceability::deleteTrace($docItem);
            return false;
        }

        if ($docItem->_no_synchro_eai) {
            return false;
        }

        // Document non finalisé dans Mediboard
        if (!$docItem->send) {
            return false;
        }

        // Si pas de catégorie on ne peut pas créer de trace
        if (!$docItem->file_category_id) {
            return false;
        }

        $file_category = $docItem->loadRefCategory();

        // Si la catégorie n'est pas éligible à une remontée d'alerte
        if (!$file_category->send_auto) {
            return false;
        }

        $where                                      = array();
        $where["files_category_to_receiver.active"] = "= '1'";
        if (!$file_category->countRelatedReceivers($where) > 0) {
            return false;
        }

        $target = $docItem->loadTargetObject();
        if (
            !$target instanceof CSejour && !$target instanceof CConsultation
            && !$target instanceof CConsultAnesth && !$target instanceof COperation
        ) {
            return false;
        }

        foreach ($file_category->loadRefRelatedReceivers($where) as $_related_receivers) {
            /** @var CFilesCategoryToReceiver $_related_receivers */
            $receiver = $_related_receivers->loadRefReceiver();

            if (!$receiver->_id) {
                continue;
            }
            CFileTraceability::createTrace($docItem, $receiver);
        }
    }
}
