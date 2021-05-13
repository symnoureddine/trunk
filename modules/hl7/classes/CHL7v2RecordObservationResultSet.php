<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use DOMNode;
use Exception;
use Ox\AppFine\Client\CAppFineClient;
use Ox\AppFine\Server\CAppFineServer;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbPath;
use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use Ox\Core\CValue;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Ftp\CSenderFTP;
use Ox\Interop\Ftp\CSenderSFTP;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Interop\Sas\CSAS;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFilesCategory;
use Ox\Mediboard\Files\CFileTraceability;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\MonitoringPatient\CObservationResult;
use Ox\Mediboard\MonitoringPatient\CObservationResultSet;
use Ox\Mediboard\MonitoringPatient\CObservationValueType;
use Ox\Mediboard\MonitoringPatient\CObservationValueUnit;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CHyperTextLink;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSenderFileSystem;
use Ox\Mediboard\System\CSourceFileSystem;
use Ox\Erp\CabinetSIH\CCabinetSIHRecordData;

/**
 * Class CHL7v2RecordObservationResultSet
 * Record observation result set, message XML
 */
class CHL7v2RecordObservationResultSet extends CHL7v2MessageXML {
  static $event_codes = array("R01");

  public $codes = array();

  /**
   * Get data nodes
   *
   * @return array Get nodes
   */
  function getContentNodes() {
    $data = $patient_results = array();

    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();

    $patient_results = $this->queryNodes("ORU_R01.PATIENT_RESULT", null, $varnull, true);

    foreach ($patient_results as $_patient_result) {
      // Patient
      $oru_patient = $this->queryNode("ORU_R01.PATIENT", $_patient_result, $varnull);
      $PID = $this->queryNode("PID", $oru_patient, $data, true);
      $data["personIdentifiers"] = $this->getPersonIdentifiers("PID.3", $PID, $sender);

      // Venue
      $oru_visit = $this->queryNode("ORU_R01.VISIT", $oru_patient, $varnull);
      $PV1 = $this->queryNode("PV1", $oru_visit, $data, true);
      if ($PV1) {
        $data["admitIdentifiers"] = $this->getAdmitIdentifiers($PV1, $sender);
      }

      // Observations
      $order_observations = $this->queryNodes("ORU_R01.ORDER_OBSERVATION", $_patient_result, $varnull);
      $data["observations"] = array();
      foreach ($order_observations as $_order_observation) {
        $tmp = array();
        // OBXs
        $oru_observations = $this->queryNodes("ORU_R01.OBSERVATION", $_order_observation, $varnull);
        foreach ($oru_observations as $_oru_observation) {
          $this->queryNodes("OBX", $_oru_observation, $tmp, true);
        }

        // OBR - on récupère uniquement le OBR concernant le OBX
        if ($oru_observations->length >= 1) {
          $this->queryNodeByIndex("OBR", $_order_observation, $tmp, 0);
        }

        if ($tmp) {
          $data["observations"][] = $tmp;
        }
      }
    }

    return $data;
  }

  /**
   * Handle event
   *
   * @param CHL7Acknowledgment $ack     Acknowledgement
   * @param CMbObject|CPatient $patient Person
   * @param array              $data    Nodes data
   *
   * @return null|string
   * @throws CHL7v2Exception
   * @throws Exception
   */
  function handle(CHL7Acknowledgment $ack = null, CMbObject $patient = null, $data = array()) {
    // Traitement du message des erreurs
    $comment = "";
    $object  = null;

    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $exchange_hl7v2->_ref_sender->loadConfigValues();
    $sender = $this->_ref_sender = $exchange_hl7v2->_ref_sender;

    // Pas d'observations
    $first_result = reset($data["observations"]);
    if (!$first_result) {
      return $exchange_hl7v2->setAckAR($ack, "E225", null, $patient);
    }

    // AppFine
    if ($sender->_configs['handle_portail_patient']
      && in_array($exchange_hl7v2->code, CHL7v2RecordObservationResultSet::$event_codes)
    ) {
      if (CModule::getActive("appFine")) {
        return CAppFineServer::handleObservationResult($ack, $data, $sender, $patient, $exchange_hl7v2);
      }
      elseif (CModule::getActive("appFineClient")) {
        return CAppFineClient::handleObservationResult($ack, $data, $sender, $patient, $exchange_hl7v2);
      }
    }

    // TAMM - SIH
    if (CModule::getActive("oxCabinetSIH") && CMbArray::get($sender->_configs, "handle_tamm_sih")
      && in_array($exchange_hl7v2->code, CHL7v2RecordObservationResultSet::$event_codes)
    ) {
      return CCabinetSIHRecordData::handleRecordObservationResultSet($ack, $data, $sender, $exchange_hl7v2);
    }

    $patientPI = CValue::read($data['personIdentifiers'], "PI");
    $venueAN   = $this->getVenueAN($sender, $data);
    $venueRI   = CValue::read($data['admitIdentifiers'], "RI");

    $sejour = new CSejour();
    if ($sender->_configs['check_identifiers']) {
      // Patient
      if (!$patientPI) {
        return $exchange_hl7v2->setAckAR($ack, "E007", null, $patient);
      }

      $IPP = CIdSante400::getMatch("CPatient", $sender->_tag_patient, $patientPI);
      // Patient non retrouvé par son IPP
      if (!$IPP->_id) {
        return $exchange_hl7v2->setAckAR($ack, "E105", null, $patient);
      }
      $patient->load($IPP->object_id);

      $sejour = null;

      // Séjour
      $sejours = $patient->getCurrSejour();
      $current_sejour = reset($sejours);

      if ($venueAN) {
        $NDA = CIdSante400::getMatch("CSejour", $sender->_tag_sejour, $venueAN);

        // Séjour non retrouvé par son NDA
        if (!$NDA->_id) {
          if (!$sender->_configs["control_nda_target_document"]) {
            $sejour = $current_sejour;
            if (!$sejour || !$sejour->_id) {
              return $exchange_hl7v2->setAckAR($ack, "E220", null, $patient);
            }
          }
          else {
            return $exchange_hl7v2->setAckAR($ack, "E205", null, $patient);
          }
        }

        if (!$sejour) {
          /** @var CSejour $sejour */
          $sejour = $NDA->loadTargetObject();
          if (!$sejour->_id) {
            return $exchange_hl7v2->setAckAR($ack, "E220", null, $patient);
          }

          if ($sejour->patient_id !== $patient->_id) {
            if (!$sender->_configs["control_nda_target_document"]) {
              $sejour = $current_sejour;
              if (!$sejour || !$sejour->_id) {
                return $exchange_hl7v2->setAckAR($ack, "E220", null, $patient);
              }
            }
            else {
              return $exchange_hl7v2->setAckAR($ack, "E606", null, $patient);
            }
          }
        }
      }
    } else {
            // Recherche à minima du patient
            if ($venueRI) {
                $sejour = new CSejour();
                $sejour->load($venueRI);

                if (!$sejour || !$sejour->_id) {
                    return $exchange_hl7v2->setAckAR($ack, "E236", null, $patient);
                }

                $patient = $sejour->loadRefPatient();
            } else {
                $this->getPID($data["PID"], $patient);
                if (CMbArray::get($sender->_configs, 'search_patient')) {
                    $patient->loadMatchingPatient(false, true, [], false, $sender->group_id);
                }
            }

      if (!$patient->_id && !$sender->_configs['type_sas']) {
        return $exchange_hl7v2->setAckAR($ack, "E219", null, $patient);
      }
    }

    // Récupération des observations
    foreach ($data["observations"] as $_observation) {
      // Récupération de la date du relevé
      $observation_dt = $this->getOBRObservationDateTime($_observation["OBR"]);
      $id_partner = $this->getIdDocumentPartner($_observation["OBR"]);
      if (CMbArray::get($sender->_configs, "handle_tamm_sih")) {
          $name = $this->getOBRServiceIdentifier($_observation["OBR"], 'CE.2');
      } else {
          $name = $this->getOBRServiceIdentifier($_observation["OBR"]);
      }

      // OBR identity identifier
      $OBR_identity_identifier = null;

      $handle_OBR_identity_identifier = $sender->_configs["handle_OBR_identity_identifier"];
      if ($handle_OBR_identity_identifier) {
        $OBR_identity_identifier = $this->queryTextNode($handle_OBR_identity_identifier, $_observation["OBR"]);
      }

      $count = 0;
      foreach ($_observation["OBX"] as $key => $_OBX) {
        $count++;

        // OBX.11 : Observation result status
        $status = $this->getObservationResultStatus($_OBX);

        // OBX.2 : Type de l'OBX
        $value_type = $this->getOBXValueType($_OBX);

        // OBX.14 : Date de l'observation
        $OBX_dateTime = $this->getOBXObservationDateTime($_OBX);
        $date         = $observation_dt ? $observation_dt : $OBX_dateTime;
        $date         = CMbDT::dateTime($date);

        // OBX.16 : Identifiant du praticien
        $praticien_id = $this->getObservationAuthor($_OBX);

        // Mode SAS ?
        $type_sas = $sender->_configs['type_sas'];

        // Cas où l'on force le rattachement :
        $object_attach_OBX = $sender->_configs["object_attach_OBX"];
        // Au type d'objet ciblé sur une catégorie
        if ($object_attach_OBX == "CFilesCategory") {
          // Chargement de la catégorie par son idex
          $observation_sub_id = $this->getOBXObservationSubID($_OBX);
          $idex = CIdSante400::getMatch("CFilesCategory", CSAS::getFilesCategoryAssociationTag($sender->group_id), $observation_sub_id);
          $files_category = new CFilesCategory();
          if ($idex->_id) {
            $files_category->load($idex->object_id);
          }

          switch ($files_category->class) {
            case "CPatient":
            case "CSejour":
            case "COperation":
              $object_attach_OBX = $files_category->class;
              break;

            default:
              $object_attach_OBX = "CMbObject";
              break;
          }
        }

        // Cas où le rattachement est au patient et qu'on ne l'a pas retrouvé, et qu'on n'est pas en mode SAS
        if (!$patient->_id && ($object_attach_OBX == "CPatient") && !$type_sas) {
          return $exchange_hl7v2->setAckAR($ack, "E219", null, $patient);
        }

        // Si pas de SAS, on recherche par date le séjour
        if (!$type_sas) {
          if (!$sejour && ($sender->_configs["object_attach_OBX"] != "CPatient")) {
            $sejours = $patient->getCurrSejour($date);
            $sejour  = reset($sejours);

            if (!$sejour || !$sejour->_id) {
              return $exchange_hl7v2->setAckAR($ack, "E220", null, $patient);
            }
          }
        }

        // Si pas de SAS, on va rechercher une config.
        if (!$type_sas) {
          switch ($object_attach_OBX) {
            // Au patient
            case "CPatient":
              $object = $patient;
              break;

            // Au séjour
            case "CSejour":
              if (!$sejour || !$sejour->_id) {
                // Recherche par date
                if ($sender->_configs["object_attach_OBX"] != "CPatient") {
                  $sejours = $patient->getCurrSejour($date, $sender->group_id, $praticien_id);
                  $sejour = reset($sejours);

                  // Si pas de SAS, on retourne une erreur
                  if (!$type_sas) {
                    if (!$sejour || !$sejour->_id ) {
                      return $exchange_hl7v2->setAckAR($ack, "E220", null, $patient);
                    }
                  }
                }
              }

              $object = $sejour;
              break;

            // À l'intervention
            case "COperation":
              $object = $this->searchOperation($date, $patient, $praticien_id, $sejour);
              break;

            default:
              // Recherche de l'objet avec la date correspondante fourni dans l'observation
              $object = $this->getObjectWithDate($date, $patient, $praticien_id, $sejour);
              break;
          }
        }

        // Rattachement à une intervention
          if (CModule::getActive('oxSIHCabinet') && CMbArray::get($sender->_configs, "handle_tamm_sih") && $data["PV1"]) {
            $PV1_50 = $this->getPV150($data["PV1"]);
            if ($PV1_50) {
                $operation = new COperation();
                $operation->load($PV1_50);
                if ($operation && $operation->_id) {
                    $object = $operation;
                }
            }
          }

        // On n'a pas retrouvé la cible, et je ne suis pas en mode SAS
        if (!$object || !$object->_id) {
          if (!$type_sas) {
            return $exchange_hl7v2->setAckAR($ack, "E301", null, $patient);
          }
        }

        $OBX_3 = $this->getObservationFilename($_OBX);
        if (!CMbArray::get($sender->_configs, "handle_tamm_sih")) {
            $name = $OBX_3 ? $OBX_3 : $name;

            if (count($_observation["OBX"]) > 1) {
                $name = $name . $key;
            }
        }

        if (CMbArray::get($sender->_configs, 'creation_date_file_like_treatment')) {
            $date_result = CMbDT::dateTime();
        }
        else {
            $date_result = $OBX_dateTime ? $OBX_dateTime : $observation_dt;
            $date_result = CMbDT::dateTime($date_result);
        }

        $file_traceability = new CFileTraceability();
        // Dans le cas d'un fichier reçu (RP / ED) on va créer un CFileTraceability
        if ($type_sas) {
          $file_traceability->received_datetime     = $exchange_hl7v2->date_production;
          $file_traceability->user_id               = CMediusers::get()->_id;
          $file_traceability->actor_class           = $sender->_class;
          $file_traceability->actor_id              = $sender->_id;
          $file_traceability->group_id              = $sender->group_id;
          $file_traceability->source_name           = CFileTraceability::getSourceName($sender);
          // Par défaut le statut est "en attente"
          $file_traceability->status                = "pending";
          $file_traceability->IPP                   = $patientPI;
          // Récupération des informations du patient du message
          $patient_hl7 = new CPatient();
          $this->getPID($data["PID"], $patient_hl7);
          $file_traceability->patient_name          = $patient_hl7->nom;
          $file_traceability->patient_birthname     = $patient_hl7->nom_jeune_fille;
          $file_traceability->patient_firstname     = $patient_hl7->prenom;
          $file_traceability->patient_date_of_birth = $patient_hl7->naissance;
          $file_traceability->datetime_object       = $OBX_dateTime;
          $file_traceability->praticien_id          = $praticien_id;
          $file_traceability->initiator             = "server";
        }

        $generate_ack = true;
        switch ($value_type) {
          // Reference Pointer to External Report
          case "RP":
            if (!$this->getReferencePointerToExternalReport($_OBX, $object, $name, $date_result, $count, $file_traceability, $id_partner, $status)) {
              return $exchange_hl7v2->setAckAR($ack, $this->codes, null, $object);
            }

            break;

          // Encapsulated Data
          case "ED":
            if (!$this->getEncapsulatedData($_OBX, $object, $name, $date_result, $count, $file_traceability, $id_partner, $status)) {
              return $exchange_hl7v2->setAckAR($ack, $this->codes, null, $object);
            }

            break;

          // Pulse Generator and Lead Observation Results
          case "ST":
          case "CWE":
          case "DTM":
          case "NM":
          case "SN":
            if (!$object instanceof COperation) {
              return $exchange_hl7v2->setAckAR($ack, "E305", null, $patient);
            }

            if (!$this->getPulseGeneratorAndLeadObservationResults($_OBX, $patient, $object, $date_result)) {
              return $exchange_hl7v2->setAckAR($ack, $this->codes, null, $object);
            }

            break;

          // Not supported
          default:
            $generate_ack = false;

            break;
          //return $exchange_hl7v2->setAckAR($ack, "E302", null, $object);
        }

        if (!$generate_ack) {
          continue;
        }

        if (!$OBR_identity_identifier) {
          if ($count < count($_observation["OBX"])) {
            continue;
          }
          return $exchange_hl7v2->setAckCA($ack, $this->codes, $comment, $object);
        }

        // On store l'idex de l'identifiant du système tiers
        $idex = new CIdSante400();
        $idex->object_class = $object->_class;
        $idex->object_id    = $object->_id;
        $idex->tag          = "OBR_$sender->_tag_hl7";
        $idex->id400        = $OBR_identity_identifier;
        $idex->loadMatchingObject();

        $idex->store();
      }
    }

    return $exchange_hl7v2->setAckCA($ack, $this->codes, $comment, $object);
  }

  /**
   * Filler number
   *
   * @param DOMNode $node node
   *
   * @return string
   */
  function getOBRFillerNumber(DOMNode $node) {
    return $this->queryTextNode("OBR.2/EI.1", $node);
  }

  /**
   * Placer number
   *
   * @param DOMNode $node node
   *
   * @return string
   */
  function getOBRPlacerNumber(DOMNode $node) {
    return $this->queryTextNode("OBR.3/EI.1", $node);
  }

  /**
   * Return the object for attach the document
   *
   * @param String   $dateTime     date
   * @param CPatient $patient      patient
   * @param String   $praticien_id praticien id
   * @param CSejour  $sejour       sejour
   *
   * @return CConsultation|COperation|CSejour
   */
  function getObjectWithDate($dateTime, CPatient $patient, $praticien_id, $sejour) {
    if ($consultation = $this->searchConsultation($dateTime, $patient, $praticien_id, $sejour)) {
      return $consultation;
    }

    if ($operation = $this->searchOperation($dateTime, $patient, $praticien_id, $sejour)) {
      return $operation;
    }

    if (!$sejour) {
      $where = array(
        "patient_id" => "= '$patient->_id'",
        "annule"     => "= '0'",
      );
      // Recherche d'une opération dans le séjour
      if ($praticien_id) {
        $where["chir_id"] = "= '$praticien_id'";
      }
      $sejours = CSejour::loadListForDate(CMbDT::date($dateTime), $where, null, 1);
      $sejour = reset($sejours);

      if (!$sejour) {
        return null;
      }
    }

    return $sejour;
  }

  /**
   * Search consultation with document
   *
   * @param String   $dateTime     date
   * @param CPatient $patient      patient
   * @param String   $praticien_id praticien id
   * @param CSejour  $sejour       sejour
   *
   * @return CConsultation|COperation|CSejour
   */
  function searchConsultation($dateTime, CPatient $patient, $praticien_id, $sejour) {
    //Recherche de la consutlation dans le séjour
    $date         = CMbDT::date($dateTime);
    $date_before  = CMbDT::date("- 2 DAY", $date);
    $date_after   = CMbDT::date("+ 1 DAY", $date);

    $consultation = new CConsultation();
    $where = array(
      "patient_id"           => "= '$patient->_id'",
      "annule"               => "= '0'",
      "plageconsult.date"    => "BETWEEN '$date_before' AND '$date'",
      "sejour_id"            => "= '$sejour->_id'",
    );

    // Praticien renseigné dans le message, on recherche par ce dernier
    if ($praticien_id) {
      $where["plageconsult.chir_id"] = "= '$praticien_id'";
    }

    $leftjoin = array("plageconsult" => "consultation.plageconsult_id = plageconsult.plageconsult_id");
    $consultation->loadObject($where, "plageconsult.date DESC", null, $leftjoin);

    //Recherche d'une consultation qui pourrait correspondre
    if (!$consultation->_id) {
      unset($where["sejour_id"]);
      $consultation->loadObject($where, "plageconsult.date DESC", null, $leftjoin);
    }

    // Consultation trouvé dans un des deux cas
    if ($consultation->_id) {
      return $consultation;
    }

    return null;
  }

  /**
   * Search operation with document
   *
   * @param String   $dateTime     date
   * @param CPatient $patient      patient
   * @param String   $praticien_id praticien id
   * @param CSejour  $sejour       sejour
   *
   * @return CConsultation|COperation|CSejour
   */
  function searchOperation($dateTime, CPatient $patient, $praticien_id, $sejour) {
    //Recherche de la consutlation dans le séjour
    $date = CMbDT::date($dateTime);
    $date_before = CMbDT::date("- 2 DAY", $date);
    $date_after  = CMbDT::date("+ 1 DAY", $date);

    $where = array(
      "sejour.patient_id"  => "= '$patient->_id'",
      "operations.annulee" => "= '0'",
      "sejour.sejour_id"   => "= '$sejour->_id'",
    );

    // Recherche d'une opération dans le séjour
    if ($praticien_id) {
      $where["operations.chir_id"] = "= '$praticien_id'";
    }

    $where[] = "'$dateTime' BETWEEN operations.entree_bloc AND operations.sortie_reveil_reel OR 
      '$dateTime' BETWEEN operations.entree_bloc AND operations.sortie_salle";

    $leftjoin = array(
      "sejour"   => "operations.sejour_id = sejour.sejour_id",
    );

    $operation = new COperation();
    $operation->loadObject($where, null, null, $leftjoin);

    if ($operation->_id) {
      return $operation;
    }

    // On recherche avec une période plus large -2 jours
    if (!$operation->_id) {
      $leftjoin = array(
        "sejour"   => "operations.sejour_id = sejour.sejour_id",
        "plagesop" => "operations.plageop_id = plagesop.plageop_id",
      );

      $where = array(
        "sejour.patient_id"  => "= '$patient->_id'",
        "operations.annulee" => "= '0'",
      );

      // Recherche d'une opération dans le séjour
      if ($praticien_id) {
        $where[] = "operations.chir_id = '$praticien_id'";
      }

      if ($sejour->_id) {
        $where[] = "sejour.sejour_id = '$sejour->_id'";
      }

      $where[] = "(operations.date BETWEEN '$date_before' AND '$date_after') OR (plagesop.date BETWEEN '$date_before' AND '$date_after')";

      $operation = new COperation();
      $operation->loadObject($where, "plagesop.date DESC", null, $leftjoin);

      if ($operation->_id) {
        return $operation;
      }
    }

    return null;
  }

  /**
   * Get PID segment
   *
   * @param DOMNode  $node       Node
   * @param CPatient $newPatient Person
   * @param array    $data       Datas
   *
   * @return void
   */
  function getPID(DOMNode $node, CPatient $newPatient, $data = null) {
    $PID5 = $this->query("PID.5", $node);
    foreach ($PID5 as $_PID5) {
      // Nom(s)
      $this->getNames($_PID5, $newPatient, $PID5);

      // Prenom(s)
      $this->getFirstNames($_PID5, $newPatient);
    }

    // Date de naissance
    $PID_7 = $this->queryTextNode("PID.7/TS.1", $node);
    $newPatient->naissance = $PID_7 ? CMbDT::date($PID_7) : null;
  }

  /**
   * Get observation date time
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBRObservationDateTime(DOMNode $node) {
    return $this->queryTextNode("OBR.7", $node);
  }

  /**
   * Get observation date time
   *
   * @param DOMNode $node DOM node
   * @param string  $CE   CE
   *
   * @return string
   */
  function getOBRServiceIdentifier(DOMNode $node, string $CE = 'CE.1') {
    $OBR_4 = $this->queryNodeByIndex("OBR.4", $node);
    return $this->queryTextNode($CE, $OBR_4);
  }

    /**
     * Get id document of partner
     *
     * @param DOMNode $node DOM node
     *
     * @return string
     */
    function getIdDocumentPartner(DOMNode $node)
    {
        return $this->queryTextNode("OBR.18", $node);
    }

  /**
   * Get value type
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBXValueType(DOMNode $node) {
    return $this->queryTextNode("OBX.2", $node);
  }

  /**
   * Get observation Sub-id
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBXObservationSubID(DOMNode $node) {
    return $this->queryTextNode("OBX.4", $node);
  }

  /**
   * Get observation date time
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getOBXObservationDateTime(DOMNode $node) {
    return $this->queryTextNode("OBX.14/TS.1", $node);
  }

  /**
   * Get observation date time
   *
   * @param DOMNode            $node   DOM node
   * @param CObservationResult $result Result
   *
   * @return string
   */
  function mappingObservationResult(DOMNode $node, CObservationResult $result) {
    // OBX-3: Observation Identifier
    $this->getObservationIdentifier($node, $result);

    // OBX-6: Units
    $this->getUnits($node, $result);

    // OBX-5: Observation Value (Varies)
    $result->value = $this->getObservationValue($node);

    // OBX-11: Observation Result Status
    $result->status =$this->getObservationResultStatus($node);
  }

  /**
   * Get observation file name
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getObservationFilename(DOMNode $node) {
    return $this->queryTextNode("OBX.3/CE.1", $node);
  }

    /**
     * Get PV1-50
     *
     * @param DOMNode $node
     *
     * @return string
     * @throws Exception
     */
    function getPV150(DOMNode $node)
    {
        return $this->queryTextNode("PV1.50/CX.1", $node);
    }

  /**
   * Get observation identifier
   *
   * @param DOMNode            $node   DOM node
   * @param CObservationResult $result Result
   *
   * @return string
   */
  function getObservationIdentifier(DOMNode $node, CObservationResult $result) {
    $identifier    = $this->queryTextNode("OBX.3/CE.1", $node);
    $text          = $this->queryTextNode("OBX.3/CE.2", $node);
    $coding_system = $this->queryTextNode("OBX.3/CE.3", $node);

    $value_type = new CObservationValueType();
    $result->value_type_id = $value_type->loadMatch($identifier, $coding_system, $text);
  }

  /**
   * Get unit
   *
   * @param DOMNode            $node   DOM node
   * @param CObservationResult $result Result
   *
   * @return string
   */
  function getUnits(DOMNode $node, CObservationResult $result) {
    $identifier    = $this->queryTextNode("OBX.6/CE.1", $node);
    $text          = $this->queryTextNode("OBX.6/CE.2", $node);
    $coding_system = $this->queryTextNode("OBX.6/CE.3", $node);

    $unit_type = new CObservationValueUnit();
    $result->unit_id = $unit_type->loadMatch($identifier, $coding_system, $text);
  }

  /**
   * Get observation value
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getObservationValue(DOMNode $node) {
    return $this->queryTextNode("OBX.5", $node);
  }

  /**
   * Get observation result status
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   */
  function getObservationResultStatus(DOMNode $node) {
    return $this->queryTextNode("OBX.11", $node);
  }

  /**
   * Return the author of the document
   *
   * @param DOMNode $node node
   *
   * @return String
   */
  function getObservationAuthor(DOMNode $node) {
    $xcn = $this->queryNode("OBX.16", $node);
    if (!$xcn) {
      return null;
    }

    $mediuser = new CMediusers();
    return $this->getDoctor($xcn, $mediuser, false);
  }

  /**
   * OBX Segment pulse generator and lead observation results
   *
   * @param DOMNode    $OBX            DOM node
   * @param CPatient   $patient        Person
   * @param COperation $operation      Opération
   * @param string     $dateTimeResult Date
   *
   * @return bool
   */
  function getPulseGeneratorAndLeadObservationResults(DOMNode $OBX, CPatient $patient, COperation $operation, $dateTimeResult) {
    $result_set = new CObservationResultSet();

    if ($dateTimeResult) {
      $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
      $sender         = $exchange_hl7v2->_ref_sender;

      $result_set->patient_id    = $patient->_id;
      $result_set->context_class = $operation->_class;
      $result_set->context_id    = $operation->_id;
      $result_set->datetime      = CMbDT::dateTime($dateTimeResult);
      $result_set->sender_id     = $sender->_id;
      $result_set->sender_class  = $sender->_class;
      if (!$result_set->loadMatchingObject()) {
        if ($msg = $result_set->store()) {
          $this->codes[] = "E302";
        }
      }
    }

    // Traiter le cas où ce sont des paramètres sans résultat utilisable
    if ($this->getObservationResultStatus($OBX) === "X") {
      return true;
    }

    $result = new CObservationResult();
    $result->observation_result_set_id = $result_set->_id;
    $this->mappingObservationResult($OBX, $result);

    /* @todo à voir si on envoi un message d'erreur ou si on continu ... */
    if ($msg = $result->store()) {
      $this->codes[] = "E304";
    }

    return true;
  }

  /**
   * Return the mime type
   *
   * @param String $type type
   *
   * @return null|string
   */
  function getFileType($type) {
    $file_type = null;

    switch ($type) {
      case "GIF":
      case "gif":
        $file_type = "image/gif";
        break;
      case "JPEG":
      case "JPG":
      case "jpeg":
      case "jpg":
        $file_type = "image/jpeg";
        break;
      case "PNG":
      case "png":
        $file_type = "image/png";
        break;
      case "RTF":
      case "rtf":
        $file_type = "application/rtf";
        break;
      case "HTML":
      case "html":
        $file_type = "text/html";
        break;
      case "TIFF":
      case "tiff":
        $file_type = "image/tiff";
        break;
      case "XML":
      case "xml":
        $file_type = "application/xml";
        break;
      case "PDF":
      case "pdf":
        $file_type = "application/pdf";
        break;
      default:
        $file_type = "unknown/unknown";
    }

    return $file_type;
  }

  /**
   * OBX Segment with encapsulated Data
   *
   * @param DOMNode           $OBX               OBX node
   * @param CMbObject         $object            Object
   * @param String            $name              name
   * @param string            $dateTimeResult    Date
   * @param int               $set_id            Numero du segment OBX parcouru
   * @param CFileTraceability $file_traceability Traçabilité du fichier
   * @param string            $id_partner        ID Partner
   * @param string            $status            Status
   *
   * @return bool
   * @throws Exception
   */
  function getEncapsulatedData($OBX, $object, $name, $dateTimeResult, $set_id, CFileTraceability $file_traceability, $id_partner = null, $status = null) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;

    //Récupération de le fichier et du type du fichier (basename)
    $observation  = $this->getObservationValue($OBX);

    $ed      = explode("^", $observation);
    $pointer = CMbArray::get($ed, 0);
    $type    = CMbArray::get($ed, 2);

    $content = base64_decode(CMbArray::get($ed, 4));

    // Où récupérer le nom du fichier ?
    if ($handle_file_name = CAppUI::gconf("hl7 ORU handle_file_name", $sender->group_id)) {
      $tab_senders = explode(",", $handle_file_name);
      foreach ($tab_senders as $_sender) {
        $temp = explode("|", $_sender);
        if (CMbArray::get($temp, 0) !== $sender->_guid) {
          continue;
        }

        $OBX_path = CMbArray::get($temp, 1);
        // Search
        if ($search = $this->queryTextNode($OBX_path, $OBX)) {
          if ($OBX_path == "OBX.5") {
            $search_tab = explode("^", $search);
            $search = CMbArray::get($search_tab, 0);
          }
          $name = $search ? $search : (CMbArray::get(pathinfo($pointer), 'filename'));
        }
      }
    }

    // Est ce qu'on a déjà le GUID du fichier dans le message HL7
    $file_guid = CMbArray::get($ed, 4);
    $guid = explode('-', $file_guid);
    if (CMbArray::get($guid, 0) && CMbArray::get($guid, 1)) {
      if (class_exists(CMbArray::get($guid, 0))) {
        $file = CMbObject::loadFromGuid($file_guid);
        if ($file && $file->_id) {
          return true;
        }
      }
    }

    $files_category = new CFilesCategory();
    // Chargement des objets associés à l'expéditeur
    // On récupère toujours une seule catégorie, et une seule source associée à l'expéditeur
    foreach ($sender->loadRefsObjectLinks() as $_object_link) {
      if ($_object_link->_ref_object instanceof CFilesCategory) {
        $files_category = $_object_link->_ref_object;
      }
    }

    // Configuration d'une catégorie reçue
    if ($observation_sub_id = $this->getOBXObservationSubID($OBX)) {
      $idex = CIdSante400::getMatch("CFilesCategory", CSAS::getFilesCategoryAssociationTag($sender->group_id), $observation_sub_id);
      if ($idex->_id) {
        $files_category->load($idex->object_id);
      }
    }

    $file_type = $this->getFileType($type);
    $ext       = strtolower($type);

    if (!$this->storeFile(
      "ED", $object, $files_category, $set_id, $dateTimeResult, $name, $ext, $file_type, $content, $file_traceability, $id_partner, $status
    )) {
      return false;
    }

    return true;
  }

  /**
   * OBX Segment with reference pointer to external report
   *
   * @param DOMNode           $OBX               OBX node
   * @param CMbObject         $object            Object
   * @param String            $name              name
   * @param string            $dateTimeResult    Date
   * @param int               $set_id            Numero du segment OBX parcouru
   * @param CFileTraceability $file_traceability Traçabilité du fichier
   * @param string            $id_partner        ID Partner
   * @param string            $status            Status
   *
   * @return bool
   * @throws Exception
   */
  function getReferencePointerToExternalReport(
    DOMNode $OBX, CMbObject $object = null, $name, $dateTimeResult, $set_id, CFileTraceability $file_traceability, $id_partner = null, $status = null
  ) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;

    //Récupération de l'emplacement et du type du fichier (full path)
    $observation  = $this->getObservationValue($OBX);

    $rp      = explode("^", $observation);
    $pointer = CMbArray::get($rp, 0);
    $type    = CMbArray::get($rp, 2);

    // Création d'un lien Hypertext sur l'objet
    if ($type == "HTML") {
      $hyperlink = new CHyperTextLink();
      $hyperlink->setObject($object);
      $hyperlink->name = $name;

      if ($sender->_configs["change_OBX_5"]) {
        $separators = explode("§§", $sender->_configs["change_OBX_5"]);
        $from = CMbArray::get($separators, 0);
        $to   = CMbArray::get($separators, 1);

        $pointer = $from && $to ? preg_replace("#$from#", "$to", $pointer) : $pointer;
      }

      $hyperlink->link = $pointer;
      $hyperlink->loadMatchingObject();

      if ($msg = $hyperlink->store()) {
        $this->codes[] = "E343";
        return false;
      }

      return true;
    }

    // Où récupérer le nom du fichier ?
    if ($handle_file_name = CAppUI::gconf("hl7 ORU handle_file_name", $sender->group_id)) {
      $tab_senders = explode(",", $handle_file_name);
      foreach ($tab_senders as $_sender) {
        $temp = explode("|", $_sender);
        if (CMbArray::get($temp, 0) !== $sender->_guid) {
          continue;
        }

        $OBX_path = CMbArray::get($temp, 1);
        // Search
        if ($search = $this->queryTextNode($OBX_path, $OBX)) {
          if ($OBX_path == "OBX.5") {
            $search_tab = explode("^", $search);
            $search = CMbArray::get($search_tab, 0);
          }
          $name = $search ? $search : (CMbArray::get(pathinfo($pointer), 'filename'));
        }
      }
    }

    // Est ce qu'on a déjà le GUID du fichier dans le message HL7
    $guid = explode('-', $pointer);
    if (CMbArray::get($guid, 0) && CMbArray::get($guid, 1)) {
      if (class_exists(CMbArray::get($guid, 0))) {
        $file = CMbObject::loadFromGuid($pointer);
        if ($file->_id) {
          return true;
        }
      }
    }

    // Chargement des objets associés à l'expéditeur
    /** @var CInteropSender $sender_link */
    $object_links = $sender->loadRefsObjectLinks();
    if (!$object_links) {
      $this->codes[] = "E340";

      return false;
    }

    $sender_link    = new CInteropSender();
    $files_category = new CFilesCategory();
    // On récupère toujours une seule catégorie, et une seule source associée à l'expéditeur
    foreach ($object_links as $_object_link) {
      if ($_object_link->_ref_object instanceof CFilesCategory) {
        $files_category = $_object_link->_ref_object;
      }

      if ($_object_link->_ref_object instanceof CInteropSender || $_object_link->_ref_object instanceof CExchangeSource) {
        $sender_link = $_object_link->_ref_object;

        continue 1;
      }
    }

    // Configuration d'une catégorie reçue
    if ($observation_sub_id = $this->getOBXObservationSubID($OBX)) {
      $idex = CIdSante400::getMatch("CFilesCategory", CSAS::getFilesCategoryAssociationTag($sender->group_id), $observation_sub_id);
      if ($idex->_id) {
        $files_category->load($idex->object_id);
      }
    }

    // Aucun expéditeur permettant de récupérer les fichiers
    if (!$sender_link->_id) {
      $this->codes[] = "E340";

      return false;
    }

    $authorized_sources = array(
      "CSenderFileSystem",
      "CSenderFTP",
      "CSenderSFTP",
      "CSourceFileSystem",
      "CSourceFTP",
      "CSourceSFTP"
    );

    // L'expéditeur n'est pas prise en charge pour la réception de fichiers
    if (!CMbArray::in($sender_link->_class, $authorized_sources)) {
      $this->codes[] = "E341";

      return false;
    }

    /** @var CSenderFileSystem|CSenderFTP|CSenderSFTP $sender_link */
    if ($sender_link instanceof CInteropSender) {
      $sender_link->loadRefsExchangesSources();
      // Aucune source permettant de récupérer les fichiers
      if (!$sender_link->_id) {
        $this->codes[] = "E342";

        return false;
      }

      $source = $sender_link->getFirstExchangesSources();
    }
    elseif ($sender_link instanceof CExchangeSource) {
      $source = $sender_link;
    }

    $path  = str_replace("\\", "/", $pointer);
    $path  = basename($path);
    $infos =  pathinfo($path);

    if ($source instanceof CSourceFileSystem) {
      $path = $source->getFullPath()."/$path";
    }

    // Exception déclenchée sur la lecture du fichier
    try {
        if ($source instanceof CSourceSFTP) {
            $content = $source->getData("$path", true);
        }
        else {
            $content = $source->getData("$path");
        }
    }
    catch (Exception $e) {
      $this->codes[] = "E345";
      return false;
    }

    if (!$type) {
      $type = CMbPath::getExtension($path);
    }

    $ext = CMbArray::get($infos, "extension");
    $file_type = $ext ? CMbPath::guessMimeType($pointer, strtolower($ext)) : $this->getFileType($type);

    if (!$this->storeFile("RP", $object, $files_category, $set_id, $dateTimeResult, $name, $ext, $file_type, $content, $file_traceability, $id_partner, $status)) {

      return false;
    }

      if (($sender_link instanceof CInteropSender && $sender_link->after_processing_action === "delete") ||
          $sender_link instanceof CExchangeSource) {
          if ($source instanceof CSourceSFTP) {
              $source->delFile($path, null, true);
          }
          else {
              $source->delFile($path);
          }
      }

    $this->codes[] = "I340";

    return true;
  }

  /**
   * Store external file
   *
   * @param string            $OBX_value_type    OBX.2 (ED|RP)
   * @param CMbObject         $object            Object
   * @param CFilesCategory    $files_category    Files category
   * @param int               $set_id            Set id
   * @param string            $dateTimeResult    DateTime result
   * @param string            $name              File name
   * @param string            $ext               File extension
   * @param string            $file_type         File type
   * @param string            $content           Content
   * @param CFileTraceability $file_traceability Traçabilité du fichier
   * @param string            $id_partner        ID Partner
   * @param string            $status            Status
   *
   * @return bool
   * @throws CHL7v2Exception
   * @throws Exception
   */
  function storeFile($OBX_value_type, $object, CFilesCategory $files_category, $set_id, $dateTimeResult, $name, $ext, $file_type,
      $content, $file_traceability = null, $id_partner = null, $status = null
  ) {
    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender         = $exchange_hl7v2->_ref_sender;

    // Par défaut, on prend la catégorie de l'expéditeur
    $files_category_mb = $files_category->_id && $files_category->nom ? $files_category : null;

    // Si on rattache le document sur un patient et qu'on a les configs
    if ($object instanceof CPatient && $sender->_configs["id_category_patient"] && ($sender->_configs["object_attach_OBX"] == "CPatient")
    ) {
      $file_category_id = $sender->_configs["id_category_patient"];
      $files_category_mb = new CFilesCategory();
      $files_category_mb->load($file_category_id);
    }

    $category_name = null;
    if ($files_category_mb && $files_category_mb->_id) {
      $category_name = $files_category_mb->nom."_";
    }

    switch ($sender->_configs["define_name"]) {
      case 'datefile_category':
        $file_name = CMbDT::dateTime($dateTimeResult)."_".$category_name.'_'.$name;
        break;

      case 'datefile_name':
        $file_name = CMbDT::dateTime($dateTimeResult).'_'.$name;
        break;

      case 'timestamp_datefile_category':
        $file_name = CMbDT::dateTime($dateTimeResult).'_'.$category_name.'_'.CMbDT::toTimestamp(CMbDT::dateTime().'_'.$name);
        break;

      case 'name':
      default:
        $file_name = $name;
    }

    if (strpos($file_name, ".".strtolower($ext)) === false) {
      $file_name .= ".".strtolower($ext);
    }

    // Gestion du CFile
      $file = new CFile();
    // Recherche d'un fichier déjà existant ?
    if ($id_partner) {
        $idex = $this->findExistFile($file, $id_partner, $sender);
        if ($idex && $idex->_id) {
            $file->load($idex->object_id);

            // Vérification que le contexte est le même
            if ($file->_id) {
                if (($file->object_id != $object->_id) || ($file->object_class != $object->_class)) {
                    $this->codes[] = "E349";
                    return false;
                }
            }
        }
    }

    if ($object) {
      $file->setObject($object);
    }
    $file->file_name = $file_name;
    $file->file_type = $file_type;
    $file->annule    = $status == 'X' ? 1 : 0;
    if (!$id_partner) {
        $file->loadMatchingObject();
    }

    if ($files_category_mb && $files_category_mb->_id && $object instanceof CPatient && $sender->_configs["id_category_patient"]
        && ($sender->_configs["object_attach_OBX"] == "CPatient")
    ) {
      $file->file_category_id = $files_category_mb->_id;
    }
    elseif ($files_category->_id && $sender->_configs["associate_category_to_a_file"]) {
      $file->file_category_id = $files_category->_id;
    }

    $file->file_date = $dateTimeResult;
    $file->doc_size  = strlen($content);

    $file->fillFields();
    $file->updateFormFields();

    if (!$content) {
        $this->codes[] = "E346";

        return false;
    }

    // Dans le cas où l'on n'a aucune cible pour le fichier de traçabilité on va le créer pour le faire pointer sur lui-même
    if ((!$object || !$object->_id) && $file_traceability) {
      $file_traceability->store();
      $file->setObject($file_traceability);
    }
    elseif ($object && $object->_id && $file_traceability && $file_traceability->_id) {
      $file_traceability->status = "auto";
    }

    if (!$file->object_class || !$file->object_id) {
      $this->codes[] = "E347";

      return false;
    }

    $file->setContent($content);

    $file->_no_synchro_eai = true;
    if ($msg = $file->store()) {
      $this->codes[] = "E343";
      $file_traceability->delete();

      return false;
    }

    if ($file_traceability && $file_traceability->_id) {
      $file_traceability->setObject($file);
      $file_traceability->store();
    }

    // Création en idex de l'ID du partenaire sur le fichier
    if ($id_partner) {
        if (!$idex || !$idex->_id) {
            $idex->object_id = $file->_id;
            if ($msg = $idex->store()) {
                $this->codes[] = "E348";
                return false;
            }
        }
    }

    // Suppression du lien dans le message et remplacement par le GUID du CFile
    $hl7_message = new CHL7v2Message;
    $hl7_message->parse($exchange_hl7v2->_message);

    /** @var CHL7v2MessageXML $xml */
    $xml = $hl7_message->toXML(null, true);
    $xml = CHL7v2Message::setIdentifier(
      $xml,
      "//ORU_R01.OBSERVATION[".$set_id."]/OBX[1]",
      $file->_guid,
      "OBX.5",
      null,
      null,
      ($OBX_value_type == "RP") ? 1 : 5,
      "^"
    );

    $exchange_hl7v2->_message = $xml->toER7($hl7_message);
    $exchange_hl7v2->store();

    if ($pointer = $sender->_configs["handle_context_url"]) {
      $hyperlink = new CHyperTextLink();
      $hyperlink->setObject($object);
      $hyperlink->name = $name;

      /** @var CPatient $patient */
      if ($object instanceof CPatient) {
        $patient = $object;
      }
      else {
        $patient = $object->loadRefPatient();
      }
      $patient->loadIPP($sender->group_id);

      $searches = array(
        "[IPP]",
      );

      $replaces = array(
        $patient->_IPP
      );

      $pointer         = str_replace($searches, $replaces, $pointer);
      $hyperlink->link = $pointer;
      $hyperlink->loadMatchingObject();

      if ($msg = $hyperlink->store()) {
        $this->codes[] = "E343";

        return false;
      }
    }

    return true;
  }

    /**
     * @param CMbObject $object
     * @param           $id_partner
     * @param CInteropSender         $group_id
     *
     * @return CIdSante400
     */
  function findExistFile(CMbObject $object, $id_partner, CInteropSender $sender) {
      return CIdSante400::getMatch($object->_class, $sender->_tag_hl7, $id_partner, $object->_id);
  }
}
