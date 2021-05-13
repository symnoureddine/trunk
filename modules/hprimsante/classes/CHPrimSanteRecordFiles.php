<?php
/**
 * @package Mediboard\Hprimsante
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hprimsante;

use DOMNode;
use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceFileSystem;

/**
 * Class CHPrimSanteRecordFiles
 * Record Result, message XML
 */
class CHPrimSanteRecordFiles extends CHPrimSanteMessageXML {
  /**
   * @see parent::getContentNodes
   */
  function getContentNodes() {
    $data = array();

    $this->queryNodes("//ORU.PATIENT_RESULT", null, $data, true); // get ALL the P segments

    return $data;
  }

  /**
   * @inheritdoc
   */
  function handle(CHPrimSanteAcknowledgment $ack, CMbObject $object, $data) {
    /** @var CExchangeHprimSante $exchange_hpr */
    $exchange_hpr = $this->_ref_exchange_hpr;
    $erreur = array();

    foreach ($data["//ORU.PATIENT_RESULT"] as $_i => $_data_patient) {
      // Permet d'identifier le numéro de ligne
      $this->loop               = $_i;
      $patient_node             = $this->queryNode("P", $_data_patient);
      $identifier               = $this->getPersonIdentifiers($patient_node);
      $this->identifier_patient = $identifier;

      if (!$identifier["identifier"]) {
        // Identifiants non transmis
        $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "01", array("P", $_i+1, $identifier), "8.3");
        continue;
      }

      // Récupération du patient par idex/match
      $patient = $this->getPatient($identifier["identifier"], $patient_node);
      // Patient non trouvé
      if (!$patient->_id) {
        $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "02", array("P", $_i+1, $identifier), "8.3");
        continue;
      }

      // Récupération de l'identifiant du sejour
      $nda_identifier = $this->getSejourIdentifier($patient_node);

      // Récupération du séjour idex/match
      $sejour = $this->getSejour($patient, $nda_identifier["sejour_identifier"], $patient_node);
      if ($sejour instanceof CHPrimSanteError) {
        $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "03", array("P", $_i+1, $identifier), "8.3");
        continue;
      }

      // Récupération des demandes
      $orders_node = $this->queryNodes("ORU.ORDER_OBSERVATION", $_data_patient);
      foreach ($orders_node as $_j => $_order) {
        $observation_dt = $this->getOBRObservationDateTime($_order);
        $praticien_id   = $this->getObservationAuthor($_order)->_id;

        //Permet d'identifier le numéro de ligne
        $loop = $_i+$_j+2;
        //Récupération des résultats
        $observations_node = $this->queryNodes("ORU.OBSERVATION", $_order);
        foreach ($observations_node as $_k => $_observation) {
          $type_observation = $this->getObservationType($_observation);
          $date             = $observation_dt ? $observation_dt : $this->getOBXObservationDateTime($_observation);

          // Recherche de l'objet avec la date correspondante fourni dans l'observation
          $object = $this->getObjectWithDate($date, $patient, $praticien_id, $sejour);
          if (!$object->_id) {
            // objet non trouvé
            $erreur[] = new CHPrimSanteError($exchange_hpr, "OBR", "03", array("OBR", $_i+1, $identifier), "32.1");
            continue;
          }

          //Permet d'identifier le numéro de ligne
          $loop += $_k+1;
          //On ne traite que les OBX qui contiennent les fichiers
          switch ($type_observation) {
            case "FIC":
              $result       = $this->getObservationResult($_observation);
              $result_parts = explode("~", $result);
              $name_editor  = CMbArray::get($result_parts, 0);
              $file_name    = CMbArray::get($result_parts, 1);
              $file_type    = $this->getFileType(CMbArray::get($result_parts, 2));

              if (!$file_name) {
                $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "04", array("OBX", $loop, $identifier), "10.6");
                break;
              }
              $this->loop = $loop;
              $this->storeFile($name_editor, $file_name, $file_type, $object, $erreur);
              break;

            default:
          }
        }
      }
    }

    return $exchange_hpr->setAck($ack, $erreur, $patient);
  }

  /**
   * Get the mediboard file type
   *
   * @param String $file_type Type file
   *
   * @return null|string
   */
  function getFileType($file_type) {
    switch ($file_type) {
      case "PDF":
        $result = "application/pdf";
        break;
      default:
        $result = null;
    }

    return $result;
  }

  /**
   * Get observation date time
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBRObservationDateTime(DOMNode $node) {
    return $this->queryTextNode("OBR/OBR.7", $node);
  }

  /**
   * Return the author of the document
   *
   * @param DOMNode $node node
   *
   * @return String
   */
  function getObservationAuthor(DOMNode $node) {
    $OBR_32 = $this->queryNode("OBR/OBR.32", $node);

    return $this->getDoctor($OBR_32, true);
  }

  /**
   * Get the observation type
   *
   * @param DOMNode $observation Observation
   *
   * @return string
   */
  function getObservationType(DOMNode $observation) {
    $xpath = new CHPrimSanteMessageXPath($observation ? $observation->ownerDocument : $this);
    return $xpath->queryTextNode("OBX/OBX.2/CE.1", $observation);
  }

  /**
   * Get the observation result
   *
   * @param DOMNode $observation Observation
   *
   * @return string
   */
  function getObservationResult(DOMNode $observation) {
    $xpath = new CHPrimSanteMessageXPath($observation ? $observation->ownerDocument : $this);
    return $xpath->queryTextNode("OBX/OBX.5", $observation);
  }

  /**
   * Get observation date time
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBXObservationDateTime(DOMNode $node) {
    return $this->queryTextNode("OBX/OBX.14/TS.1", $node);
  }

  /**
   * Return the object for attach the document
   *
   * @param String   $date         date
   * @param CPatient $patient      patient
   * @param String   $praticien_id praticien id
   * @param CSejour  $sejour       sejour
   *
   * @return CConsultation|COperation|CSejour
   */
  function getObjectWithDate($date, $patient, $praticien_id, $sejour) {
    //Recherche de la consutlation dans le séjour
    $date         = CMbDT::date($date);
    $date_before  = CMbDT::date("- 2 DAY", $date);
    $consultation = new CConsultation();
    $where = array(
      "patient_id"           => "= '$patient->_id'",
      "annule"               => "= '0'",
      "plageconsult.date"    => "BETWEEN '$date_before' AND '$date'",
      "plageconsult.chir_id" => "= '$praticien_id'",
      "sejour_id"            => "= '$sejour->_id'",
    );

    $leftjoin = array("plageconsult" => "consultation.plageconsult_id = plageconsult.plageconsult_id");
    $consultation->loadObject($where, "plageconsult.date DESC", null, $leftjoin);

    //Recherche d'une consultation qui pourrait correspondre
    if (!$consultation->_id) {
      unset($where["sejour_id"]);
      $consultation->loadObject($where, "plageconsult.date DESC", null, $leftjoin);
    }

    //Consultation trouvé dans un des deux cas
    if ($consultation->_id) {
      return $consultation;
    }

    //Recherche d'une opération dans le séjour
    $where = array(
      "sejour.patient_id"  => "= '$patient->_id'",
      "plagesop.date"      => "BETWEEN '$date_before' AND '$date'",

      "operations.annulee" => "= '0'",
      "sejour.sejour_id"   => "= '$sejour->_id'",
    );

    if ($praticien_id) {
      $where["operations.chir_id"] = "= '$praticien_id'";
    }

    $leftjoin = array(
      "plagesop" => "operations.plageop_id = plagesop.plageop_id",
      "sejour"   => "operations.sejour_id = sejour.sejour_id",
    );
    $operation = new COperation();
    $operation->loadObject($where, "plagesop.date DESC", null, $leftjoin);

    if ($operation->_id) {
      return $operation;
    }

    return $sejour;
  }

  /**
   * Store the file
   *
   * @param String   $prefix    Prefix for the name of file
   * @param String   $file_name Name of file
   * @param String   $file_type Type file
   * @param CMbObject $object    Object
   * @param array    &$erreur   Error
   *
   * @return bool
   */
  function storeFile($prefix, $file_name, $file_type, $object, &$erreur) {
    /** @var CInteropSender $sender */
    $sender       = $this->_ref_sender;
    $exchange_hpr = $this->_ref_exchange_hpr;

    // On récupère toujours une seule catégorie, et une seule source associée à l'expéditeur
    $sender_link    = new CInteropSender();
    $files_category = new CFilesCategory();

    $object_links = $sender->loadRefsObjectLinks();

    foreach ($object_links as $_object_link) {
      if ($_object_link->_ref_object instanceof CFilesCategory) {
        $files_category = $_object_link->_ref_object;
      }

      if ($_object_link->_ref_object instanceof CInteropSender) {
        $sender_link = $_object_link->_ref_object;

        continue 1;
      }
    }

    // Aucun expéditeur permettant de récupérer les fichiers
    if (!$sender_link->_id) {
      $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "05", array("OBX", $this->loop, $this->identifier_patient), "5");
      return false;
    }

    $sender_link->loadRefsExchangesSources();
    // Aucune source permettant de récupérer les fichiers
    if (!$sender_link->_id) {
      $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "06", array("OBX", $this->loop, $this->identifier_patient), "5");
      return false;
    }

    /** @var CExchangeSource $_source */
    $source = $sender_link->getFirstExchangesSources();
    if ($source instanceof CSourceFileSystem) {
      /** @var CSourceFileSystem $_source */
      $path = $source->getFullPath($file_name);
    }
    if ($source instanceof CSourceFTP) {
      /** @var CSourceFTP $_source */
      $path = $file_name;
    }

    // Exception déclenchée sur la lecture du fichier
    try {
      $content = $source->getData("$path");
    }
    catch (Exception $e) {
      $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "07", array("OBX", $this->loop, $this->identifier_patient), "5");
      return false;
    }

    // Gestion du CFile
    $file = new CFile();
    $file->setObject($object);
    $file->file_name = "$prefix $file_name";
    $file->file_type = $file_type;
    $file->loadMatchingObject();
    $file->file_date = "now";
    $file->doc_size  = strlen($content);

    $file->fillFields();
    $file->updateFormFields();

    $file->setContent($content);

    if ($msg = $file->store()) {
      $erreur[] = new CHPrimSanteError($exchange_hpr, "P", "08",
                    array("OBX", $this->loop, $this->identifier_patient), "5", CMbString::removeAllHTMLEntities($msg));
      return false;
    }

    if ($sender_link->_delete_file !== false) {
      $source->delFile($path);
    }

    return true;
  }
}