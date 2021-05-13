<?php
/**
 * @package Mediboard\cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Interop\Eai\CEAIOperator;
use Ox\Interop\Eai\CExchangeDataFormat;
use Ox\Mediboard\Patients\CPatient;

/**
 * Class COperatorCDA
 * Operator CDA
 */
class COperatorCDA extends CEAIOperator {
  /**
   * Event
   *
   * @param CExchangeDataFormat $data_format data format
   *
   * @return string
   */
  function event(CExchangeDataFormat $data_format) {
    $msg               = $data_format->_message;
    $data_format->loadRefsInteropActor();
    $sender = $data_format->_ref_sender;

    /** @var CCDAEvent $evt */
    $evt               = $data_format->_family_message;
    $evt->_data_format = $data_format;

    try {
      // Création de l'échange
      $exchange_cda = new CExchangeCDA();
      $exchange_cda->load($data_format->_exchange_id);

      /** @var CCDADomDocument $dom_evt */
      $dom_evt = $evt->getCDAEvent($msg);
      if (!$dom_evt) {
        // Générer un acquittement d'erreur
        return null;
      }

      // Gestion des notifications ?
      if (!$exchange_cda->_id) {
        $exchange_cda->populateEchange($data_format, $evt);
        $exchange_cda->message_valide = 1;
      }

      $exchange_cda->loadRefsInteropActor();
      //$doc_errors = $dom_evt->schemaValidate();
      $doc_errors = true;
      // Acquittement d'erreur d'un document XML recu non valide
      if ($doc_errors !== true) {
        $exchange_cda->populateErrorEchange(CAppUI::tr("CExchangeCDA-message-Invalid document"), 0, "erreur");
        // Générer un acquittement d'erreur
        return null;
      }

      $exchange_cda->date_production = CMbDT::dateTime();
      $exchange_cda->store();

      // Pas de traitement du message
      if (!$data_format->_to_treatment) {
        return null;
      }

      $dom_evt->_ref_exchange_cda = $exchange_cda;
      $dom_evt->_ref_sender       = $sender;

      $data = array();
      return self::handleEvent($data, $exchange_cda, $dom_evt);
    }
    catch(Exception $e) {
      // Générer un acquittement d'erreur
      return null;
    }

    // Générer un acquittement d'erreur
    return null;
  }

  /**
   * handle event
   *
   * @param array           $data         data
   * @param CExchangeCDA    $exchange_cda exchange
   * @param CCDADomDocument $dom_evt      event cda
   *
   * @return string
   */
  static function handleEvent($data = array(), CExchangeCDA $exchange_cda, CCDADomDocument $dom_evt) {
    $data = array_merge($data, $dom_evt->getContentNodes());

    $object = new CPatient();
    return $dom_evt->handle($object, $data);
  }
}

