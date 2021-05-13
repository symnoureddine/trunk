<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use DOMNode;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Medicament\CMedicament;
use Ox\Mediboard\Medicament\CMedicamentArticle;
use Ox\Mediboard\Pharmacie\CStockSejour;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\Stock\CProduct;
use Ox\Mediboard\Stock\CProductStockService;
use Ox\Mediboard\Stock\CStockMouvement;

/**
 * Class CHL7v2ReceiveMasterFilesNotification
 * Master files notification, message XML HL7
 */
class CHL7v2ReceiveMasterFilesNotification extends CHL7v2MessageXML {
  static $event_codes = array ("M15");

  /**
   * Get contents
   *
   * @return array
   */
  function getContentNodes() {
    $data  = array();

    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender       = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();

    $this->_ref_sender = $sender;

    // Software Segment
    $this->queryNodes("SFT", null, $data, true);

    // User Authentication Credential
    $this->queryNode("UAC", null, $data, true);

    // Master File Identification
    $this->queryNode("MFI", null, $data, true);

    $MF_INV_ITEM = $this->queryNodes("MFN_M15.MF_INV_ITEM", null, $varnull, true);
    $data["items"] = array();
    foreach ($MF_INV_ITEM as $_MF_INV_ITEM) {
      $tmp = array();
      // MFE - Master File Entry
      $this->queryNode("MFE", $_MF_INV_ITEM, $tmp, null);

      // IIM - Inventory Item Master
      $this->queryNode("IIM", $_MF_INV_ITEM, $tmp, null);

      $data["items"][] = $tmp;
    }

    return $data;
  }

  /**
   * Handle receive order message
   *
   * @param CHL7Acknowledgment $ack     Acknowledgment
   * @param CMbObject          $patient Person
   * @param array              $data    Data
   *
   * @return string|void
   */
  function handle(CHL7Acknowledgment $ack = null, CMbObject $patient = null, $data = array()) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();

    $this->_ref_sender = $sender;

    // Récupération des items
    foreach ($data['items'] as $_item) {
      // CProduct
      $product_code = $this->getProductItemCode($_item['IIM']);
      if (!$product_code) {
        return $exchange_hl7v2->setAckAR($ack, 'E7001', null, $patient);
      }

      $product = new CProduct();
      $product->code = $product_code;
      $product->loadMatchingObjectEsc();

      if (!$product->_id) {
        return $exchange_hl7v2->setAckAR($ack, 'E7002', null, $patient);
      }

      $stock_cible = null;
      switch (CMbArray::get($sender->_configs, "handle_IIM_6")) {
        // CStockSejour
        case 'sejour':
          $NDA_id = $this->getInventoryLocationCode($_item['IIM']);
          if (!$NDA_id) {
            return $exchange_hl7v2->setAckAR($ack, 'E7003', null, $patient);
          }

          $NDA = CIdSante400::getMatch('CSejour', $sender->_tag_sejour, $NDA_id);
          if (!$NDA->_id) {
            return $exchange_hl7v2->setAckAR($ack, 'E7008', null, $patient);
          }

          $article = CMedicamentArticle::get($product->code);

          $stock_sejour = CStockSejour::getFromCIP($article->getId(), $NDA->object_id);
          if (!$stock_sejour->_id) {
            if ($msg = $stock_sejour->store()) {
              return $exchange_hl7v2->setAckAR($ack, 'E7009', null, $patient);
            }
          }

          $stock_cible = $stock_sejour;

          break;

        // CProductStockService
        case 'service':
          $service_item_code = $this->getInventoryLocationCode($_item['IIM']);
          if (!$service_item_code) {
            return $exchange_hl7v2->setAckAR($ack, 'E7003', null, $patient);
          }

          $service = new CService();
          $service->code = $service_item_code;
          $service->loadMatchingObjectEsc();

          if (!$service->_id) {
            return $exchange_hl7v2->setAckAR($ack, 'E7004', null, $patient);
          }

          $stock_service = CProductStockService::getFromCode($product->code, $service->_id);
          if (!$stock_service->_id) {
            if ($msg = $stock_service->store()) {
              return $exchange_hl7v2->setAckAR($ack, 'E7005', null, $patient);
            }
          }

          $stock_cible = $stock_service;

          break;

        default:
          return $exchange_hl7v2->setAckAR($ack, 'E7007', null, $patient);
      }

      if (!$stock_cible || !$stock_cible->_id) {
        return $exchange_hl7v2->setAckAR($ack, 'E7010', null, $patient);
      }

      // CStockMouvement
      $stock_mouvement = new CStockMouvement();
      $stock_mouvement->type     = $this->getProcedureCode($_item['IIM']);
      if (in_array($stock_mouvement->type, CStockMouvement::$_reduction_stock_source)) {
        $stock_mouvement->setSource($stock_cible);
      }
      else {
        $stock_mouvement->setCible($stock_cible);
      }
      $stock_mouvement->etat     = 'en_cours';
      $stock_mouvement->quantite = $this->getQuantity($_item['IIM']);
      $stock_mouvement->datetime = CMbDT::dateTime($this->getDatetime($_item['IIM']));
      $stock_mouvement->_increment_stock = false;
      if ($msg = $stock_mouvement->store()) {
        return $exchange_hl7v2->setAckAR($ack, 'E7006', $msg, $patient);
      }

      return $exchange_hl7v2->setAckAA($ack, 'I7000', null, $stock_mouvement);
    }
  }

  /**
   * Get product item code
   *
   * @param DOMNode $node ORC node
   *
   * @return string
   */
  function getProductItemCode(DOMNode $node) {
    return $this->queryTextNode("IIM.1/CWE.1", $node);
  }

  /**
   * Get service item code
   *
   * @param DOMNode $node ORC node
   *
   * @return string
   */
  function getInventoryLocationCode(DOMNode $node) {
    return $this->queryTextNode("IIM.6/CWE.1", $node);
  }

  /**
   * Get service item code
   *
   * @param DOMNode $node ORC node
   *
   * @return string
   */
  function getDatetime(DOMNode $node) {
    return $this->queryTextNode("IIM.7/TS.1", $node);
  }

  /**
   * Get service item code
   *
   * @param DOMNode $node ORC node
   *
   * @return string
   */
  function getQuantity(DOMNode $node) {
    if (!$IIM_12 = $this->queryTextNode("IIM.12", $node)) {
      return null;
    }

    return abs($IIM_12);
  }

  /**
   * Get procedure code
   *
   * @param DOMNode $node ORC node
   *
   * @return string
   */
  function getProcedureCode(DOMNode $node) {
    return $this->queryTextNode("IIM.14", $node);
  }
}
