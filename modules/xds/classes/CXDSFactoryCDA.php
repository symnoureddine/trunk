<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Xds;

use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbSecurity;
use Ox\Core\CMbXPath;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Dmp\CDMP;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\InteropResources\CInteropResources;
use Ox\Interop\Xds\Structure\CXDSClass;
use Ox\Interop\Xds\Structure\CXDSConfidentiality;
use Ox\Interop\Xds\Structure\CXDSContentType;
use Ox\Interop\Xds\Structure\CXDSDocumentEntryAuthor;
use Ox\Interop\Xds\Structure\CXDSEventCodeList;
use Ox\Interop\Xds\Structure\CXDSExtrinsicObject;
use Ox\Interop\Xds\Structure\CXDSFormat;
use Ox\Interop\Xds\Structure\CXDSHealthcareFacilityType;
use Ox\Interop\Xds\Structure\CXDSPracticeSetting;
use Ox\Interop\Xds\Structure\CXDSRegistryPackage;
use Ox\Interop\Xds\Structure\CXDSType;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Cim10\CCodeCIM10;
use Ox\Mediboard\Files\CSubmissionLot;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Créé le XDS en fonction du CDA.
 */
class CXDSFactoryCDA extends CXDSFactory {

  /**
   * @see parent::extractData
   */
  function extractData() {
    /** @var CCDAFactory $factory */
    $factory                 = $this->mbObject;
    $this->document          = $factory->mbObject;
    $this->targetObject      = $factory->targetObject;
    $this->id_classification = 0;
    $this->id_external       = 0;

    $mediuser                = CMediusers::get();
    $specialty               = $mediuser->loadRefOtherSpec();

    // Pour un séjour : On prend l'établissement du séjour et non l'étab de la fonction
    if ($this->targetObject instanceof CSejour) {
      $group = $this->targetObject->loadRefEtablissement();
    }
    elseif ($this->targetObject instanceof COperation) {
      $group = $this->targetObject->loadRefSejour()->loadRefEtablissement();
    }
    else {
      $group = $mediuser->loadRefFunction()->loadRefGroup();
    }

    $identifiant             = CXDSTools::getIdEtablissement(true, $group, $this->type)."/$mediuser->_id";
    $this->specialty         = $specialty->code."^".$specialty->libelle."^".$specialty->oid;
    $this->xcn_mediuser      = CXDSTools::getXCNMediuser($identifiant, $mediuser->_p_last_name, $mediuser->_p_first_name);
    $this->xon_etablissement = CXDSTools::getXONetablissement($group->text, CXDSTools::getIdEtablissement(false, $group, $this->type), $this->type);

    $this->xpath = new CMbXPath($factory->dom_cda);
    $this->xpath->registerNamespace("cda", "urn:hl7-org:v3");

    $this->patient_id  = $this->getID($factory->patient, $factory->receiver);
    if ($this->type == "DMP" || $this->type == "SISRA") {
      $this->ins_patient = $this->getINSNIR($factory->patient);
    }
    $uuid = CMbSecurity::generateUUID();
    $this->uuid["registry"]  = $uuid."1";
    $this->uuid["extrinsic"] = $uuid."2";
    $this->uuid["signature"] = $uuid."3";

    $this->valueset_factory = CXDSValueSet::getFactory($this->type);
  }

  /**
   * @see parent::createRegistryPackage
   */
  function createRegistryPackage($id) {
    /** @var CCDAFactory $factory */
    $factory               = $this->mbObject;
    $cla_id                = &$this->id_classification;
    $ei_id                 = &$this->id_external;
    $ins                   = $this->ins_patient;
    $this->name_submission = $id;
    $specialty             = $this->specialty;
    $object                = $this->targetObject;
    $patient_id            = $this->patient_id;
    $valueset_factory      = $this->valueset_factory;
    $praticien             = $factory->practicien;

    $registry = new CXDSRegistryPackage($id);

    //date de soumission
    $registry->setSubmissionTime(array(CXDSTools::getTimeUtc()));

    //title
    $registry->setTitle($factory->nom);

    //PS qui envoie le document
    $document = new CXDSDocumentEntryAuthor("cla$cla_id", $id, true);
    $this->setClaId();
    // On prend le praticien de la consult/du séjour (sas envoi)
    $author = $this->getPerson($praticien);
    $document->setAuthorPerson(array($author));

    $spec = $praticien->loadRefOtherSpec();
    if ($spec->libelle) {
      $document->setAuthorSpecialty(array("$spec->code^$spec->libelle^$spec->oid"));
    }
    else {
      $document->setAuthorSpecialty(array($specialty));
    }

    // Pour un séjour : On prend l'établissement du séjour et non l'étab de la fonction
    if ($this->targetObject instanceof CSejour) {
      $author_organization = $this->targetObject->loadRefEtablissement();
    }
    elseif ($this->targetObject instanceof COperation) {
      $author_organization = $this->targetObject->loadRefSejour()->loadRefEtablissement();
    }
    else {
      $author_organization = $praticien->loadRefFunction()->loadRefGroup();
    }

    if ($author_organization->_id) {
      $institution = CXDSTools::getXONetablissement(
        $author_organization->text,
        CXDSTools::getIdEtablissement(false, $author_organization, $this->type), $this->type
      );
      $document->setAuthorInstitution(array($institution));
    }
    else {
      //Institution qui envoie le document
      $document->setAuthorInstitution(array($this->xon_etablissement));
    }

    $registry->appendDocumentEntryAuthor($document);

    $entry = $valueset_factory::getContentTypeCode($object);
    if ($factory->level == 3) {
      $entry = CDMPValueSet::getContentTypeCode($object);
    }

    $content = new CXDSContentType("cla$cla_id", $id, $entry["code"]);
    $this->setClaId();
    $content->setCodingScheme(array($entry["codeSystem"]));
    $content->setContentTypeCodeDisplayName($entry["displayName"]);
    $registry->setContentType($content);

    //spécification d'un SubmissionSet ou d'un folder, ici submissionSet
    $registry->setSubmissionSet("cla$cla_id", $id, false);
    $this->setClaId();

    //patient du document
    if ($this->type == "DMP") {
      $registry->setPatientId("ei$ei_id", $id, $ins);
    }
    elseif ($this->type == "SISRA") {
        if ($ins) {
            $registry->setPatientId("ei$ei_id", $id, $ins);
        }
        else {
            $registry->setPatientId("ei$ei_id", $id, $patient_id);
        }
    }
    else {
      $registry->setPatientId("ei$ei_id", $id, $patient_id);
    }
    $this->setEiId();
    $receiver = $factory->receiver;
    //OID de l'instance serveur
    $only_oid_root = $this->type != "DMP";
    $oid_instance = CMbOID::getOIDOfInstance($registry, $receiver, $only_oid_root);
    $registry->setSourceId("ei$ei_id", $id, $oid_instance);
    $this->setEiId();

    //OID unique
    $oid = CMbOID::getOIDFromClass($registry, $receiver);
    $cxds_submissionlot = new CSubmissionLot();
    $cxds_submissionlot->date = "now";
    $cxds_submissionlot->type = $this->type;
    if ($msg = $cxds_submissionlot->store()) {
      throw new CMbException($msg);
    }

    $this->id_submission = $cxds_submissionlot->_id;
    $this->oid["lot"] = "$oid.$cxds_submissionlot->_id";
    $registry->setUniqueId("ei$ei_id", $id, $this->oid["lot"]);
    $this->setEiId();

    return $registry;
  }

  /**
   * @see parent::createExtrinsicObject
   */
  function createExtrinsicObject($id, $lid = null, $hide = true, $metadata = null, $status = null) {
    /** @var CCDAFactory $factory */
    $factory           = $this->mbObject;
    $cla_id            = &$this->id_classification;
    $ei_id             = &$this->id_external;
    $patient_id        = $this->patient_id;
    $ins               = $this->ins_patient;
    $hide_patient      = $this->hide_patient;
    $hide_representant = $this->hide_representant;
    $hide_ps           = $this->hide_ps;
    $service           = $factory->service_event;
    $industry          = $factory->industry_code;
    $praticien         = $factory->practicien;
    $valueset_factory  = $this->valueset_factory;

    $this->appendNameDocument($id);

    $extrinsic = new CXDSExtrinsicObject($id, "text/xml", $status, $lid);

    //effectiveTime en UTC
    if ($factory->date_creation) {
      $extrinsic->setSlot("creationTime", array(CXDSTools::getTimeUtc($factory->date_creation)));
    }

    //languageCode
    $extrinsic->setSlot("languageCode", array($factory->langage));

    // repositoryUniqueId
    if ($factory->level == 1) {
      $extrinsic->setSlot("repositoryUniqueId", array(CMbArray::get($metadata, 'repositoryUniqueId')));
    }

    //legalAuthenticator XCN
    $legalAuthenticator = $this->getPerson($praticien);
    $extrinsic->setSlot("legalAuthenticator", array($legalAuthenticator));

    // Size (si document non signé alors le calcul de la taille est effectué sur l'ensemble de son contenu => tout le XML du CDA)
    if ($metadata) {
      $extrinsic->setSlot("size", array(CMbArray::get($metadata, 'size')));
    }
    elseif ($this->size) {
      $extrinsic->setSlot("size", array($this->size));
    }

    // URI
    if ($this->uri) {
      $extrinsic->setSlot("URI", array($this->uri));
    }

    // Hash
    if ($metadata) {
      $extrinsic->setSlot("hash", array(CMbArray::get($metadata, 'hash')));
    }
    elseif ($this->hash) {
      $extrinsic->setSlot("hash", array($this->hash));
    }

    //documentationOf/serviceEvent/effectiveTime/low en UTC
    if ($service["time_start"]) {
      $extrinsic->setSlot("serviceStartTime", array(CXDSTools::getTimeUtc($service["time_start"])));
    }

    //documentationOf/serviceEvent/effectiveTime/high en UTC
    if ($service["time_stop"]) {
      $extrinsic->setSlot("serviceStopTime", array(CXDSTools::getTimeUtc($service["time_stop"])));
    }

    //recordTarget/patientRole/id
    if ($this->type == "DMP") {
      $extrinsic->setSlot("sourcePatientId", array($ins));
    }
    else {
      $extrinsic->setSlot("sourcePatientId", array($patient_id));
    }

    //recordtarget/patientRole
    $extrinsic->setSlot("sourcePatientInfo", $this->getSourcepatientInfo($factory->patient));

    // Ajout du titre
    $extrinsic->setTitle($factory->nom);

    //Auteur du document
    $document = new CXDSDocumentEntryAuthor("cla$cla_id", $id);
    $this->setClaId();

    //author/assignedAuthor
    $author = $this->getPerson($praticien);
    $document->setAuthorPerson(array($author));

    //author/assignedAuthor/code
    $spec = $praticien->loadRefOtherSpec();
    if ($spec->libelle) {
      $document->setAuthorSpecialty(array("$spec->code^$spec->libelle^$spec->oid"));
    }

    //author/assignedAuthor/representedOrganization - si absent, ne pas renseigner
    //si nom pas présent - champ vide
    //si id nullflavor alors 6-7-10 vide

    // Pour un séjour : On prend l'établissement du séjour et non l'étab de la fonction
    if ($this->targetObject instanceof CSejour) {
      $author_organization = $this->targetObject->loadRefEtablissement();
    }
    elseif ($this->targetObject instanceof COperation) {
      $author_organization = $this->targetObject->loadRefSejour()->loadRefEtablissement();
    }
    else {
      $author_organization = $praticien->loadRefFunction()->loadRefGroup();
    }

    if ($author_organization->_id) {
      $institution = CXDSTools::getXONetablissement(
        $author_organization->text,
        CXDSTools::getIdEtablissement(false, $author_organization, $this->type),
        $this->type
      );
      $document->setAuthorInstitution(array($institution));
    }
    $extrinsic->appendDocumentEntryAuthor($document);

    //confidentialityCode
    $confidentialite = $factory->confidentialite;
    $confid = new CXDSConfidentiality("cla$cla_id", $id, $confidentialite["code"]);
    $this->setClaId();
    $confid->setCodingScheme(array($confidentialite["codeSystem"]));
    $confid->setName($confidentialite["displayName"]);
    $extrinsic->appendConfidentiality($confid);

    if ($hide_ps) {
      $confid2 = CXDSConfidentiality::getMasquage("cla$cla_id", $id, "MASQUE_PS");
      $this->setClaId();
      $extrinsic->appendConfidentiality($confid2);
    }

    if ($hide_patient) {
      $confid3 = CXDSConfidentiality::getMasquage("cla$cla_id", $id, "INVISIBLE_PATIENT");
      $this->setClaId();
      $extrinsic->appendConfidentiality($confid3);
    }

    if ($hide_representant) {
      $confid4 = CXDSConfidentiality::getMasquage("cla$cla_id", $id, "INVISIBLE_REPRESENTANTS_LEGAUX");
      $this->setClaId();
      $extrinsic->appendConfidentiality($confid4);
    }

    //documentationOf/serviceEvent/code - table de correspondance
    if (!$service["nullflavor"] && $service["nullflavor"] !== null) {
      $eventSystem = $service["oid"];
      $eventCode = $service["code"];
      switch ($service["type_code"]) {
        case "cim10":
          $cim10 = CCodeCIM10::get($eventCode);
          $libelle = $cim10->libelle;
          break;
        case "ccam":
          $ccam = CDatedCodeCCAM::get($eventCode);
          $libelle = $ccam->libelleCourt;
          break;
        default:
          // CAS VSM METADATA
          $libelle = CMbArray::get($service, "libelle");
      }

      $event = new CXDSEventCodeList("cla$cla_id", $id, $eventCode);
      $this->setClaId();
      $event->setCodingScheme(array($eventSystem));
      $event->setName($libelle);
      $extrinsic->appendEventCodeList($event);
    }

        //En fonction d'un corps structuré
        $entry = $valueset_factory::getFormatCode($factory->mediaType, $factory->templateId);
        if ($factory->level == 3) {
            if ($factory->type_cda == 'VSM') {
                $data_valueset = CInteropResources::loadEntryJV(
                    CMbArray::get(CDMPValueSet::$JDV, "formatCode"),
                    CCDAFactory::$vsm_code_jdv,
                    CDMPValueSet::$type
                );
            } elseif ($factory->type_cda == 'LDL-EES') {
                $data_valueset = CInteropResources::loadEntryJV(
                    CMbArray::get(CDMPValueSet::$JDV, "formatCode"),
                    CCDAFactory::$ldl_ees_code_jdv,
                    CDMPValueSet::$type
                );
            } elseif ($factory->type_cda == 'LDL-SES') {
                $data_valueset = CInteropResources::loadEntryJV(
                    CMbArray::get(CDMPValueSet::$JDV, "formatCode"),
                    CCDAFactory::$ldl_ses_code_jdv,
                    CDMPValueSet::$type
                );
            } else {
                $data_valueset = [];
            }

                $entry = array(
                    "codingScheme" => CMbArray::get($data_valueset, "codeSystem"),
                    "name"         => CMbArray::get($data_valueset, "displayName"),
                    "formatCode"   => CMbArray::get($data_valueset, "code"),
                );
        }

    $codingScheme = CMbArray::get($entry, "codingScheme");
    $name         = CMbArray::get($entry, "name");
    $formatCode   = CMbArray::get($entry, "formatCode");

    $format = new CXDSFormat("cla$cla_id", $id, $formatCode);
    $this->setClaId();
    $format->setCodingScheme(array($codingScheme));
    $format->setName($name);
    $extrinsic->setFormat($format);

    //componentOf/encompassingEncounter/location/healthCareFacility/code
    $healtcare     = $factory->healt_care;
    $healt         = new CXDSHealthcareFacilityType("cla$cla_id", $id, $healtcare["code"]);
    $this->setClaId();
    $healt->setCodingScheme(array($healtcare["codeSystem"]));
    $healt->setName($healtcare["displayName"]);
    $extrinsic->setHealthcareFacilityType($healt);
    $this->health_care_facility = $this->health_care_facility ? $this->health_care_facility : $healtcare;

    //documentationOf/serviceEvent/performer/assignedEntity/representedOrganization/standardIndustryClassCode
    $pratice    = new CXDSPracticeSetting("cla$cla_id", $id, $industry["code"]);
    $this->setClaId();
    $pratice->setCodingScheme(array($industry["codeSystem"]));
    $pratice->setName($industry["displayName"]);
    $this->practice_setting = $this->practice_setting ? $this->practice_setting : $industry;
    $extrinsic->setPracticeSetting($pratice);

    //code
    $code = $factory->code;
    $type = new CXDSType("cla$cla_id", $id, $code["code"]);
    $this->setClaId();
    $type->setCodingScheme(array($code["codeSystem"]));
    $type->setName($code["displayName"]);
    $extrinsic->setType($type);

    //code
    // TODO : Passer par CAsipValueSet
    list($classCode, $oid, $name) = $factory->level == 3 ? CDMPValueSet::getClassCode(CMbArray::get($code, "code"), $factory->type_cda) : $valueset_factory::getClassCode(CMbArray::get($code, "code"));
    $classification = new CXDSClass("cla$cla_id", $id, $classCode);
    $this->setClaId();
    $classification->setCodingScheme(array($oid));
    $classification->setName($name);
    $extrinsic->setClass($classification);

    //recordTarget/patientRole/id

    if ($this->type == "DMP") {
      $extrinsic->setPatientId("ei$ei_id", $id, $ins);
    }
    elseif ($this->type == "SISRA") {
        if ($ins) {
            $extrinsic->setPatientId("ei$ei_id", $id, $ins);
        }
        else {
            $extrinsic->setPatientId("ei$ei_id", $id, $patient_id);
        }
    }
    else {
      $extrinsic->setPatientId("ei$ei_id", $id, $patient_id);
    }
    $this->setEiId();

    //id - root
    $root = $factory->id_cda;
    $this->oid["extrinsic"] = $root;
    $extrinsic->setUniqueId("ei$ei_id", $id, $root);
    $this->setEiId();

    return $extrinsic;
  }
}
