<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Loinc\CLoinc;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Classe pour les Document Item
 */
class CCDAFactoryDocItem extends CCDAFactory {
  /**
   * @see parent::extractData
   */
  function extractData() {
    /** @var CDocumentItem $docItem */
    $docItem                = $this->mbObject;
    $this->realm_code       = "FR";
    $this->langage          = $docItem->language;
    $this->size             = $docItem->doc_size;
    $valueset_factory = $this->valueset_factory = CXDSValueSet::getFactory($this->xds_type);

    //Récupération du dernier log qui correspond à la date de création de cette version
    $last_log = $docItem->loadLastLog();
    $this->date_creation = $last_log->date;
    $this->date_author   = $last_log->date;
    $this->targetObject  = $object = $docItem->loadTargetObject();
    if ($object instanceof CConsultAnesth) {
      $this->targetObject = $object = $object->loadRefConsultation();
    }
    $this->practicien = $object->loadRefPraticien();

    $this->patient    = $object->loadRefPatient();
    $this->patient->loadLastINS();
    $this->patient->loadIPP();

    // Création du dossier médical du patient => nécessaire pour CDA structuré
    CDossierMedical::dossierMedicalId($this->patient->_id, $this->patient->_class);

    $this->mbObject   = $docItem;
    $this->root       = CMbOID::getOIDFromClass($docItem, $this->receiver);

    $this->practicien->loadRefFunction();
    $this->practicien->loadRefOtherSpec();

      if ($object instanceof CSejour) {
          $group = $object->loadRefEtablissement();
      } elseif ($object instanceof COperation) {
          $group = $object->loadRefSejour()->loadRefEtablissement();
      }
      else {
          $group = new CGroups();
          $group->load($this->practicien->_group_id);
      }

    // Idex positionné sur l'établissement dans le module CDA
    // CDA structuré
        if ($this->level == 3 && $this->type_cda) {
            $this->healt_care = CDMPValueSet::getHealthcareFacilityTypeCode($group);
        } else {
            $this->healt_care = $valueset_factory::getHealthcareFacilityTypeCode($group);
        }

        $this->version = $docItem->_version;
        if ($docItem instanceof CFile) {
            if (isset($docItem->_file_name_cda) && $docItem->_file_name_cda) {
                $this->nom = $docItem->_file_name_cda;
            } else {
                $this->nom = basename($docItem->file_name);
            }
        } else {
            $this->nom = $docItem->nom;
        }

    if ($docItem->loadLastId400("DMP")->_id) {
      $this->id_cda = $this->id_cda_lot = $docItem->_ref_last_id400->id400;
    }
    else {
      $this->id_cda_lot = "$this->root.$docItem->_id";
      $this->id_cda     = "$this->id_cda_lot.$this->version";
    }

    $confidentialite = "N";
    if ($docItem->private) {
      $confidentialite = "R";
    }
    $this->confidentialite = $valueset_factory::getConfidentialityCode($confidentialite);

    $type = array();
    if ($docItem->type_doc_dmp) {
      $type = explode("^", $docItem->type_doc_dmp);
    }

    // Pour un CDA structuré avec un code LOINC
    if ($this->type_cda && $this->code_loinc_cda) {
      $this->code = CLoinc::getTypeCode($this->code_loinc_cda);
    }
    // Par défaut, on prend les jeux de valeurs ASIP, DMP ou XDS
    else {
      $this->code = $valueset_factory::getTypeCode(CMbArray::get($type, 1));
    }

    //conformité HL7
    $this->templateId[] = $this->createTemplateID("2.16.840.1.113883.2.8.2.1", "HL7 France");

    if ($this->xds_type == "DMP") {
      //Conformité CI-SIS
      $this->templateId[] = $this->createTemplateID("1.2.250.1.213.1.1.1.1", "CI-SIS");
    }

    //Confirmité IHE XSD-SD => contenu non structuré
    if ($this->level == 1) {
      $this->templateId[] = $this->createTemplateID("1.3.6.1.4.1.19376.1.2.20", "IHE XDS-SD");
    }

    // CDA structuré
    if ($this->level == 3 && $this->type_cda) {
      $this->industry_code = CDMPValueSet::getPracticeSettingCode();
    }
    else {
      $this->industry_code = $valueset_factory::getPracticeSettingCode();
    }
    // Pour les CDA non structurés (DMP et XDS), on génère un PDF
    if ($this->level == 1) {
      $mediaType = "application/pdf";

      //Génération du PDF
      if ($docItem instanceof CFile) {
        $path = $docItem->_file_path;
        switch ($docItem->file_type) {
          case "image/tiff":
            $mediaType = "image/tiff";
            break;
          case "application/pdf":
            $mediaType = $docItem->file_type;
            $path = CCdaTools::generatePDFA($docItem->_file_path);
            break;
          case "image/jpeg":
          case "image/jpg":
            $mediaType = "image/jpeg";
            break;
          case "application/rtf":
            $mediaType = "text/rtf";
            break;
          default:
            $type = $this->receiver->type;
            if ($type == "DMP" || $type == "Zepra") {
              throw new CMbException("$type-msg-Document type authorized in $type|pl");
            }
            else {
              throw new CMbException("XDS-msg-Document type authorized in XDS|pl");
            }
          /*$docItem->convertToPDF();
          $file = $docItem->loadPDFconverted();
          $path = CCdaTools::generatePDFA($file->_file_path);*/
        }
      }
      else {
        if ($msg = $docItem->makePDFpreview(1, 0)) {
          throw new CMbException($msg);
        }
        $file = $docItem->_ref_file;
        $path = CCdaTools::generatePDFA($file->_file_path);
      }
      $this->file      = $path;
      $this->mediaType = $mediaType;
    }
    else {
      $this->file      = $docItem;
      $this->mediaType = $docItem->file_type;

      // Ajout des templateId pour les sections afin de construire le structuredBody du CDA
      if ($this->type_cda) {
        $this->mappingTypeCDAWithTemplateId($this->type_cda);
        $this->getStructureCDAFromType($this->type_cda);
      }
    }

    $service["nullflavor"] = null;

    switch (get_class($object)) {
      case CSejour::class:
        /** @var CSejour $object CSejour */

        $dp = $object->DP;
        $service["time_start"] = $object->entree;
        $service["time_stop"]  = $object->sortie;
        $service["executant"]  = $object->loadRefPraticien();
        if ($dp) {
          $service["oid"]       = "2.16.840.1.113883.6.3";
          $service["code"]      = $dp;
          $service["type_code"] = "cim10";
        }
        else {
          $service["nullflavor"] = "UNK";
        }
        break;
      case COperation::class:
        /** @var COperation $object COperation */
        $no_acte = 0;
        foreach ($object->loadRefsActesCCAM() as $_acte_ccam) {
          if ($_acte_ccam->code_activite === "4" || !$_acte_ccam->_check_coded  || $no_acte >= 1) {
            continue;
          }

          $service["time_start"] = $_acte_ccam->execution;
          $service["time_stop"]  = "";
          $service["code"]       = $_acte_ccam->code_acte;
          $service["oid"]        = "1.2.250.1.213.2.5";
          $_acte_ccam->loadRefExecutant();
          $service["executant"] = $_acte_ccam->_ref_executant;
          $service["type_code"] = "ccam";
          $no_acte++;
        }

        if ($no_acte === 0) {
          $service["time_start"] = $object->debut_op;
          $service["time_stop"]  = $object->fin_op;
          $service["executant"]  = $object->loadRefPraticien();
          $service["nullflavor"] = "UNK";
        }
        break;
      case CConsultation::class:
        /** @var CConsultation $object CConsultation */
        $object->loadRefPlageConsult();


        $no_acte = 0;
        foreach ($object->loadRefsActesCCAM() as $_acte_ccam) {
          if (!$_acte_ccam->_check_coded || $_acte_ccam->code_activite === "4" || $no_acte >= 1) {
            continue;
          }

          $service["time_start"] = $_acte_ccam->execution;
          $service["time_stop"]  = "";
          $service["code"]       = $_acte_ccam->code_acte;
          $service["oid"]        = "1.2.250.1.213.2.5";
          $_acte_ccam->loadRefExecutant();
          $service["executant"] = $_acte_ccam->_ref_executant;
          $service["type_code"] = "ccam";
          $no_acte++;
        }

        if ($no_acte === 0) {
          $service["time_start"] = $object->_datetime;
          $service["time_stop"]  = $object->_date_fin;
          $service["executant"]  = $object->loadRefPraticien();
          $service["nullflavor"] = "UNK";
        }
        break;
      default:
    }

    // Dans le cas du CDA VSM, le code dans documentationOf/serviceEvent est fixe Code LOINC : 34117-2
    if ($this->level == 3 && $this->type_cda == CCDAFactory::$type_vsm) {
      $service["type_code"]  = $this->type_cda;
      $service["code"]       = CLoinc::$code_vsm;
      $service["nullflavor"] = "";
      $service["oid"]        = CLoinc::$oid_loinc;
      // On fixe le libellé en dur parce que le code LOINC est utilisé avec 2 libelles différents
      $service["libelle"]    = 'Historique et clinique';
    }

    // Cas des lettres de liaisons
    if ($this->level == 3 && ($this->type_cda == CCDAFactory::$type_ldl_ees || $this->type_cda == CCDAFactory::$type_ldl_ses)) {
      $service["type_code"]  = $this->type_cda;
      $service["code"]       = 'IMP';
      $service["nullflavor"] = "";
      $service["oid"]        = '2.16.840.1.113883.5.4';
      $service["libelle"]    = 'Hospitalisation';
    }

    $this->service_event = $service;

    if ($this->old_version) {
      $oid = CMbOID::getOIDFromClass($docItem, $this->receiver);
      $this->old_version = "$oid.$this->old_id.$this->old_version";
    }
  }
}
