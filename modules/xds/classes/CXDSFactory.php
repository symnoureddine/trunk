<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Xds;

use DateTime;
use DateTimeZone;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\CCDAFactoryDocItem;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\Xds\Structure\CXDSAssociation;
use Ox\Interop\Xds\Structure\CXDSClass;
use Ox\Interop\Xds\Structure\CXDSConfidentiality;
use Ox\Interop\Xds\Structure\CXDSDocumentEntryAuthor;
use Ox\Interop\Xds\Structure\CXDSEventCodeList;
use Ox\Interop\Xds\Structure\CXDSExtrinsicObject;
use Ox\Interop\Xds\Structure\CXDSFormat;
use Ox\Interop\Xds\Structure\CXDSHasMemberAssociation;
use Ox\Interop\Xds\Structure\CXDSHealthcareFacilityType;
use Ox\Interop\Xds\Structure\CXDSPracticeSetting;
use Ox\Interop\Xds\Structure\CXDSRegistryObjectList;
use Ox\Interop\Xds\Structure\CXDSRegistryPackage;
use Ox\Interop\Xds\Structure\CXDSType;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Files\CSubmissionLotToDocument;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CPaysInsee;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * Permet de générer le XDs en fonction des champs remplis
 */
class CXDSFactory implements IShortNameAutoloadable {
  /** @var CMbObject  */
  public $mbObject;
  /** @var CMbObject  */
  public $targetObject;
  public $document;
  public $hide_patient;
  public $hide_ps;
  public $hide_representant;
  public $name_submission;
  public $id_classification;
  public $id_external;
  public $patient_id;
  public $xpath;
  public $xcn_mediuser;
  public $xon_etablissement;
  public $specialty;
  public $ins_patient;
  public $practice_setting;
  public $health_care_facility;
  public $iti57;
  public $size;
  public $hash;
  public $repository;
  public $doc_uuid;
  public $id_submission;
  public $type;
  public $uri;
  public $uuid          = array();
  public $oid           = array();
  public $name_document = array();

  /** @var CXDSValueSet|CDMPValueSet */
  public $valueset_factory;

  /**
   * Création de la classe en fonction de l'objet passé
   *
   * @param CMbObject|CCDAFactory $mbObject objet mediboard
   *
   * @return CXDSFactory
   */
  static function factory($mbObject) {
    switch (get_class($mbObject)) {
      case CCDAFactoryDocItem::class:
        $class = new CXDSFactoryCDA($mbObject);
        break;
      default:
        $class = new self($mbObject);
    }

    return $class;
  }

  /**
   * Constructeur
   *
   * @param CMbObject $mbObject mediboard object
   */
  function __construct($mbObject) {
    $this->mbObject = $mbObject;
  }

  /**
   * Extrait les données de l'objet nécessaire au XDS
   *
   * @return void
   */
  function extractData() {
  }

  /**
   * Génération de la requête XDS57 concernant le dépubliage et l'archivage
   *
   * @param string $uuid         Identifiant du document dans le registre
   * @param string $archivage    Type archivage
   * @param string $masquage     Type masquage
   * @param string $id_extrinsic ID extrinsic
   * @param array  $metadata     Metadata
   *
   * @return CXDSXmlDocument
   * @throws CMbException
   */
  function generateXDS57($uuid, $archivage = null, $masquage = null, $id_extrinsic = null, $metadata = null) {
    $id_registry = $this->uuid["registry"];

    $class = new CXDSRegistryObjectList();

    //Ajout du lot de soumission
    $registry = $this->createRegistryPackage($id_registry);
    $class->appendRegistryPackage($registry);

    // Cas de l'archivage
    if ($archivage) {
      $NewStatus      = "";
      $OriginalStatus = "";
      switch ($archivage) {
        case "unpublished":
          $NewStatus      = "urn:asip:ci-sis:2010:StatusType:Deleted";
          $OriginalStatus = "urn:oasis:names:tc:ebxml-regrep:StatusType:Approved";
          break;
        case "archived":
          $NewStatus      = "urn:asip:ci-sis:2010:StatusType:Archived";
          $OriginalStatus = "urn:oasis:names:tc:ebxml-regrep:StatusType:Approved";
          break;
        case "unarchived":
          $NewStatus      = "urn:oasis:names:tc:ebxml-regrep:StatusType:Approved";
          $OriginalStatus = "urn:asip:ci-sis:2010:StatusType:Archived";
          break;
        default:
      }

      $asso = new CXDSAssociation("association01", $id_registry, $uuid, "urn:ihe:iti:2010:AssociationType:UpdateAvailabilityStatus");
      $asso->setSlot("OriginalStatus", array("$OriginalStatus"));
      $asso->setSlot("NewStatus", array("$NewStatus"));
      $class->appendAssociation($asso);
    }

    // Cas du masquage
    if ($masquage !== null) {
      $version = CMbArray::get($metadata,"version");

      $asso = new CXDSAssociation("association01", $id_registry, $id_extrinsic);
      $asso->setSlot("SubmissionSetStatus", array("Original"));
      $asso->setSlot("PreviousVersion", array("$version"));
      $class->appendAssociation($asso);

      switch ($masquage) {
        case "0":
          $this->hide_ps = true;
          break;
        case "1":
          $this->hide_patient = true;
          break;
        case "2":
          $this->hide_representant = true;
          break;
        default:
          $this->hide_patient = false;
      }

      // Ajout d'un document
      //$extrinsic = $this->createExtrinsicObject($id_extrinsic, $uuid, true, $metadata);
      //$class->appendExtrinsicObject($extrinsic);
    }

    if ($archivage) {
      return $class->toXML();
    }
    // Ajout du document dans le toXML()
    else {
      return $class->toXML($metadata, $id_extrinsic, $uuid, $masquage);
    }
  }

  /**
   * Génère le corps XDS
   *
   * @throws CMbException
   * @return CXDSXmlDocument
   */
  function generateXDS41() {
    $id_registry  = $this->uuid["registry"];
    $id_document  = $this->uuid["extrinsic"];
    $doc_uuid     = $this->doc_uuid;

    // Ajout du lot de soumission
    $class = new CXDSRegistryObjectList();

    // Métadonnée du lot de soumission
    $registry = $this->createRegistryPackage($id_registry);
    $class->appendRegistryPackage($registry);

    // Ajout d'un document
    $extrinsic = $this->createExtrinsicObject($id_document);
    $class->appendExtrinsicObject($extrinsic);

    // Ajout des associations
    $asso1 = $this->createAssociation("association01", $id_registry, $id_document);
    $class->appendAssociation($asso1);

    // Si le document est déjà existant
    if ($doc_uuid) {
      $asso4 = $this->createAssociation("association02", $id_document, $doc_uuid, true, true);
      $class->appendAssociation($asso4);
    }

    // Création dans mediboard du lot de soumission
    $cxds_submissionlot_document = new CSubmissionLotToDocument();
    $cxds_submissionlot_document->submissionlot_id = $this->id_submission;
    $cxds_submissionlot_document->setObject($this->document);
    if ($msg = $cxds_submissionlot_document->store()) {
      throw new CMbException($msg);
    }

    return $class->toXML();
  }

  /**
   * Génère le corps XDM
   *
   * @throws CMbException
   * @return CXDSXmlDocument
   */
  function generateXDS32($status) {
    $id_registry  = $this->uuid["registry"];
    $id_document  = $this->uuid["extrinsic"];
    $doc_uuid     = $this->doc_uuid;

    // Ajout du lot de soumission
    $class = new CXDSRegistryObjectList();

    // Métadonnée du lot de soumission
    $registry = $this->createRegistryPackage($id_registry);
    $class->appendRegistryPackage($registry);

    // Ajout d'un document
    $extrinsic = $this->createExtrinsicObject($id_document, null, true, null, $status);
    $class->appendExtrinsicObject($extrinsic);

    // Ajout des associations
    $asso1 = $this->createAssociation("association01", $id_registry, $id_document);
    $class->appendAssociation($asso1);

    // Si le document est déjà existant
    if ($doc_uuid) {
      $asso4 = $this->createAssociation("association02", $id_document, $doc_uuid, true, true);
      $class->appendAssociation($asso4);
    }

    // Création dans mediboard du lot de soumission
    $cxds_submissionlot_document = new CSubmissionLotToDocument();
    $cxds_submissionlot_document->submissionlot_id = $this->id_submission;
    $cxds_submissionlot_document->setObject($this->document);
    if ($msg = $cxds_submissionlot_document->store()) {
      throw new CMbException($msg);
    }

    return $class->toXML();
  }

  /**
   * Garde en mémoire le nom des documents
   *
   * @param String $name Nom du document
   *
   * @return void
   */
  function appendNameDocument($name) {
    array_push($this->name_document, $name);
  }

  /**
   * Retourne l'INS présent dans le CDA
   *
   * @param CPatient $patient Patient
   *
   * @return string
   */
  function getIns ($patient) {
    $ins = null;
    //@todo: faire l'INSA
    $last_ins = $patient->_ref_last_ins;
    if ($last_ins) {
      $ins = $last_ins->ins;
    }
    $comp5 = "INS-C";
    $comp4 = "1.2.250.1.213.1.4.2";
    $comp4 = "&$comp4&ISO";
    $comp1 = $ins;

    $result = "$comp1^^^$comp4^$comp5";
    return $result;
  }

  /**
   * Retourne le NIR présent dans le CDA
   *
   * @param CPatient $patient Patient
   *
   * @return string
   */
  function getNIR($patient) {
    $comp5 = "NH";
    $comp4 = CAppUI::conf("dmp NIR_OID");
    $comp4 = "&$comp4&ISO";
    $comp1 = $patient->matricule;

    $result = "$comp1^^^$comp4^$comp5";
    return $result;
  }

  /**
   * Retourne le NIR présent dans le CDA
   *
   * @param CPatient $patient
   *
   * @return string
   * @throws \Exception
   */
  function getINSNIR(CPatient $patient) {
    $comp5 = "NH";
    $comp4 = CAppUI::conf("dmp NIR_OID");
    $comp4 = "&$comp4&ISO";
    $comp1 = $patient->getINSNIR();

    $result = "$comp1^^^$comp4^$comp5";
    return $result;
  }

  /**
   * Incrémente l'identifiant des classifications
   *
   * @return void
   */
  function setClaId() {
    $this->id_classification++;
  }

  /**
   * Incrémente l'identifiant des externals
   *
   * @return void
   */
  function setEiId() {
    $this->id_external++;
  }

  /**
   * Création du lot de soumission
   *
   * @param String $id Identifiant du lot de soumission
   *
   * @throws CMbException
   * @return CXDSRegistryPackage
   */
  function createRegistryPackage($id) {
  }

  /**
   * Création  d'un document
   *
   * @param String $id       Identifiant
   * @param String $lid      Lid
   * @param bool   $hide     Est ce qu'on met le masquage dans la trame ?
   * @param array  $metadata Metadata
   * @param string $status   Status
   *
   * @return CXDSExtrinsicObject
   */
  function createExtrinsicObject($id, $lid = null, $hide = true, $metadata = null, $status = null) {
  }

  /**
   * Création du document de la signature
   *
   * @param String $id            Identifiant
   * @param String $creation_time Creation Time
   *
   * @return CXDSExtrinsicObject
   */
  function createSignature($id, $creation_time = null) {
    // Ajout des metadata pour le lot de soumission
    $cla_id    = &$this->id_classification;
    $ei_id     = &$this->id_external;
    $ins       = $this->ins_patient;
    $specialty = $this->specialty;
    /** @var CCDAFactory $factory */
    $factory   = $this->mbObject;
    $praticien = $factory->practicien;

    //Création du document
    $extrinsic = new CXDSExtrinsicObject($id, "text/xml");
    $extrinsic->setSlot("creationTime"      , array($creation_time ?: CXDSTools::getTimeUtc()));
    $extrinsic->setSlot("languageCode"      , array("art"));
    // on prend le praticien (sas envoi)
    $legalAuthenticator = $this->getPerson($praticien);
    $extrinsic->setSlot("legalAuthenticator", array($legalAuthenticator));
    $extrinsic->setSlot("serviceStartTime"  , array($creation_time ?: CXDSTools::getTimeUtc()));
    $extrinsic->setSlot("serviceStopTime"   , array($creation_time ?: CXDSTools::getTimeUtc()));

    //patientId du lot de submission
    $extrinsic->setSlot("sourcePatientId", array($ins));
    $extrinsic->setTitle("Source");

    //identique à celui qui envoie
    $document = new CXDSDocumentEntryAuthor("cla$cla_id", $id);
    $this->setClaId();
    // On prend le praticien de la consult/du séjour (sas envoi)
    $author = $this->getPerson($praticien);
    $document->setAuthorPerson(array($author));
    //$document->setAuthorPerson(array($this->xcn_mediuser));
    //$document->setAuthorSpecialty(array($specialty));

    //author/assignedAuthor/code
    $spec = $praticien->loadRefOtherSpec();
    if ($spec->libelle) {
      $document->setAuthorSpecialty(array("$spec->code^$spec->libelle^$spec->oid"));
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

    //$document->setAuthorInstitution(array($this->xon_etablissement));
    $extrinsic->appendDocumentEntryAuthor($document);

    $classification = new CXDSClass("cla$cla_id", $id, "urn:oid:1.3.6.1.4.1.19376.1.2.1.1.1");
    $this->setClaId();
    $classification->setCodingScheme(array("URN"));
    $classification->setName("Digital Signature");
    $extrinsic->setClass($classification);

    $confid = new CXDSConfidentiality("cla$cla_id", $id, "N");
    $this->setClaId();
    $confid->setCodingScheme(array("2.16.840.1.113883.5.25"));
    $confid->setName("Normal");
    $extrinsic->appendConfidentiality($confid);

    $confid2 = CXDSConfidentiality::getMasquage("cla$cla_id", $id, "MASQUE_PS");
    $this->setClaId();
    $extrinsic->appendConfidentiality($confid2);

    $confid3 = CXDSConfidentiality::getMasquage("cla$cla_id", $id, "INVISIBLE_PATIENT");
    $this->setClaId();
    $extrinsic->appendConfidentiality($confid3);

    $event = new CXDSEventCodeList("cla$cla_id", $id, "1.2.840.10065.1.12.1.14");
    $this->setClaId();
    $event->setCodingScheme(array("1.2.840.10065.1.12"));
    $event->setName("Source");
    $extrinsic->appendEventCodeList($event);

    $format = new CXDSFormat("cla$cla_id", $id, "http://www.w3.org/2000/09/xmldsig#");
    $this->setClaId();
    $format->setCodingScheme(array("URN"));
    $format->setName("Default Signature Style");
    $extrinsic->setFormat($format);

    $healtcare = $this->health_care_facility;
    $healt = new CXDSHealthcareFacilityType("cla$cla_id", $id, $healtcare["code"]);
    $this->setClaId();
    $healt->setCodingScheme(array($healtcare["codeSystem"]));
    $healt->setName($healtcare["displayName"]);
    $extrinsic->setHealthcareFacilityType($healt);

    $industry = $this->practice_setting;
    $pratice  = new CXDSPracticeSetting("cla$cla_id", $id, $industry["code"]);
    $this->setClaId();
    $pratice->setCodingScheme(array($industry["codeSystem"]));
    $pratice->setName($industry["displayName"]);
    $extrinsic->setPracticeSetting($pratice);

    $type = new CXDSType("cla$cla_id", $id, "E1762");
    $this->setClaId();
    $type->setCodingScheme(array("ASTM"));
    $type->setName("Full Document");
    $extrinsic->setType($type);

    //identique au lot de submission
    $extrinsic->setPatientId("ei$ei_id", $id, $ins);
    $this->setEiId();

    //identifiant de la signature
    $this->oid["signature"] = $this->oid["lot"]."0";
    $extrinsic->setUniqueId("ei$ei_id", $id, $this->oid["signature"]);
    $this->setEiId();

    return $extrinsic;
  }

  /**
   * Création des associations
   *
   * @param String $id     Identifiant
   * @param String $source Source
   * @param String $target Cible
   * @param bool   $sign   Association de type signature
   * @param bool   $rplc   Remplacement
   *
   * @return CXDSHasMemberAssociation
   */
  function createAssociation($id, $source, $target, $sign = false, $rplc = false) {
    $hasmember = new CXDSHasMemberAssociation($id, $source, $target, $sign, $rplc);
    if (!$sign || !$rplc) {
      $hasmember->setSubmissionSetStatus(array("Original"));
    }

    return $hasmember;
  }

  /**
   * Retourne la person
   *
   * @param CMediusers $praticien CMediusers
   *
   * @return string
   */
  function getPerson(CMediusers $praticien) {
    $comp1 = "";
    $comp2 = $praticien->_p_last_name;
    $comp3 = $praticien->_p_first_name;

    if ($this->type == "DMP") {
      if (!$praticien->adeli && !$praticien->rpps) {
        return null;
      }

      $comp9 = "1.2.250.1.71.4.2.1";
      $comp10 = "D";

      if ($praticien->adeli) {
        $comp1 = "0$praticien->adeli";
      }

      if ($praticien->rpps) {
        $comp1 = "8$praticien->rpps";
      }
      $comp13 = $this->getTypeId($comp1);
      $result = "$comp1^$comp2^$comp3^^^^^^&$comp9&ISO^$comp10^^^$comp13";
    }
    // Nom, prénom et si on connait RPPS ou ADELI on renseigne (ils ont un algo de rapprochement en fonction des infos qu'on leur donne
    elseif ($this->type == 'SISRA') {
        if ($praticien->rpps || $praticien->adeli) {
            if ($praticien->adeli) {
                $comp1 = "0$praticien->adeli";
            }

            if ($praticien->rpps) {
                $comp1 = "8$praticien->rpps";
            }

            $comp9  = "1.2.250.1.71.4.2.1";
            $comp10 = "D";
            $comp13 = $this->getTypeId($comp1);
            $result = "$comp1^$comp2^$comp3^^^^^^&$comp9&ISO^$comp10^^^$comp13";
        }
        else {
            $result = "$comp1^$comp2^$comp3";
        }
    }
    else {
      $comp1 = "";
      $comp2 = $praticien->_p_last_name;
      $comp3 = $praticien->_p_first_name;
      $result = "$comp1^$comp2^$comp3";
    }

    return $result;
  }

  /**
   * Retourne le type d'id passé en paramètre
   *
   * @param String $id String
   *
   * @return string
   */
  function getTypeId($id) {
    $result = "IDNPS";
    if (strpos("/", $id) !== false) {
      $result = "EI";
    }
    if (strlen($id) === 22) {
      $result = "INS-C";
    }
    /*if (strlen($id) === 12) {
      $result = "INS-A";
    }*/
    return $result;
  }

  /**
   * Transforme une chaine date au format time CDA
   *
   * @param String $date      String
   * @param bool   $naissance false
   *
   * @return string
   */
  function getTimeToUtc($date, $naissance = false) {
    if (!$date) {
      return null;
    }
    if ($naissance) {
      $date = Datetime::createFromFormat("Y-m-d", $date);
      return $date->format("Ymd");
    }
    $timezone = new DateTimeZone(CAppUI::conf("timezone"));
    $date     = new DateTime($date, $timezone);

    return $date->format("YmdHisO");
  }

  /**
   * Retourne le sourcepatientinfo
   *
   * @param CPatient $patient patient
   *
   * @return String[]
   */
  function getSourcepatientInfo($patient) {
    $source_info = array();
    if ($this->type == "SISRA") {
      if ($patient->nom_jeune_fille) {
          $pid5 = "PID-5|$patient->nom_jeune_fille^$patient->_p_first_name^^^^^L";
          $source_info[] = $pid5;
      }
    }

    $pid5 = "PID-5|$patient->_p_last_name^$patient->_p_first_name^^^^^D";
    $source_info[] = $pid5;
    $date = $this->getTimeToUtc($patient->_p_birth_date, true);
    $pid7 = "PID-7|$date";
    if ($this->type == "SISRA") {
      $pid7 =  $pid7."000000";
    }
    $source_info[] = $pid7;
    $sexe = mb_strtoupper($patient->sexe);
    $pid8 = "PID-8|$sexe";
    $source_info[] = $pid8;
    if ($this->type !== "SISRA" && ($patient->_p_street_address || $patient->_p_city || $patient->_p_postal_code)) {
      $addresses = preg_replace("#[\t\n\v\f\r]+#", " ", $patient->_p_street_address, PREG_SPLIT_NO_EMPTY);
      $pid11 = "PID-11|$addresses^^$patient->_p_city^^$patient->_p_postal_code";
      $source_info[] = $pid11;
    }

    if ($this->type == "SISRA") {
      // Ajout du lieu de naissance (si on a toutes les infos et que le patient est né en France)
      $pays_naissance_insee = CPaysInsee::getPaysByNumerique($patient->pays_naissance_insee);
      if ($patient->lieu_naissance && $patient->cp_naissance && $patient->pays_naissance_insee
          && $pays_naissance_insee->alpha_3 == "FRA"
      ) {
        $pid11_birth_location = "PID-11|^^$patient->lieu_naissance^^$patient->cp_naissance^$pays_naissance_insee->alpha_3^BDL";
      }
      else {
        $pid11_birth_location = "PID-11|^^^^00000^UKN^BDL";
      }
      $source_info[] = $pid11_birth_location;

      // Ajout du lieu de résidence
      $addresses = preg_replace("#[\t\n\v\f\r]+#", " ", $patient->_p_street_address, PREG_SPLIT_NO_EMPTY);
      $city = $patient->_p_city ? $patient->_p_city : "UKN";
      $city = $patient->_p_postal_code ? $patient->_p_postal_code : "00000";
      $pays_insee = CPaysInsee::getPaysByNumerique($patient->pays_insee);
      $pid11_home = "PID-11|$addresses^^$patient->_p_city^^$patient->_p_postal_code^$pays_insee->alpha_3^H";
      $source_info[] = $pid11_home;
    }

    if ($patient->_p_phone_number) {
      $pid13 = "PID-13|$patient->_p_phone_number";
      $source_info[] = $pid13;
    }
    if ($patient->_p_mobile_phone_number) {
      $pid14 = "PID-14|$patient->_p_mobile_phone_number";
      $source_info[] = $pid14;
    }
    $pid16 = "PID-16|{$this->getMaritalStatus($patient->situation_famille)}";
    $source_info[] = $pid16;

    // Pour Sisra : Il faut que des majuscules, pas d'accent, pas d'apostrophe, pas de caractères spéciaux
    if ($this->type == "SISRA") {
      $source_info_formated = array();
      foreach ($source_info as $_source_info) {
        // Suppression des caracteres accentués
        $data_formated = CMbString::removeDiacritics($_source_info);
        // Passage uniquement de majuscule
        $data_formated = mb_strtoupper($data_formated);
        // transformation de l'apostrophe et du tiret pour les noms composés en espace
        //$data_formated = str_replace("-", " ", $data_formated);
        $data_formated = str_replace("'", " ", $data_formated);
        $source_info_formated[] = $data_formated;
      }
    }
    else {
      $source_info_formated = $source_info;
    }

    return $source_info_formated;
  }

  /**
   * Return the Marital Status
   *
   * @param String $status mediboard status
   *
   * @return string
   */
  function getMaritalStatus($status) {
    switch ($status) {
      case "S":
        $ce = "S";
        break;
      case "M":
        $ce = "M";
        break;
      case "G":
        $ce = "G";
        break;
      case "D":
        $ce = "D";
        break;
      case "W":
        $ce = "W";
        break;
      case "A":
        $ce = "A";
        break;
      case "P":
        $ce = "P";
        break;
      default:
        $ce = "U";
    }
    return $ce;
  }

  /**
   * Retourne l'OID du patient
   *
   * @param CPatient         $patient  Patient
   * @param CInteropReceiver $receiver Receiver
   *
   * @return string
   */
  function getID(CPatient $patient, CInteropReceiver $receiver = null) {
    $comp1 = $patient->_IPP ? $patient->_IPP : $patient->_id;

    $only_oid_root = $this->type != "DMP";

    $oid = CMbOID::getOIDOfInstance($patient, $receiver, $only_oid_root);
    $comp4 = "&$oid&ISO";
    $comp5 = "^PI";

    $result = "$comp1^^^$comp4".$comp5;

    return $result;
  }
}
