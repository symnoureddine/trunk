<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Files;

use Exception;
use Ox\AppFine\Client\CAppFineClient;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbMetaObjectPolyfill;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CStoredObject;
use Ox\Core\Module\CModule;
use Ox\Erp\CabinetSIH\CCabinetSIH;
use Ox\Interop\Dmp\CDMPDocument;
use Ox\Interop\Dmp\CDMPTools;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CInteropActor;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\InteropResources\CInteropResources;
use Ox\Interop\SIHCabinet\CSIHCabinet;
use Ox\Interop\Sisra\CSisraDocument;
use Ox\Interop\Sisra\CSisraTools;
use Ox\Interop\Xds\CXDSDocument;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CDestinataire;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Messagerie\CElectronicDelivery;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\IPatientRelated;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\Forms\CExObject;


/**
 * The CDocumentItem class
 */
class CDocumentItem extends CMbObject {
  public $file_category_id;

  public $etat_envoi;
  public $author_id;
  public $private;
  public $annule;
  public $doc_size;
  public $type_doc_dmp;
  public $type_doc_sisra;
  public $remis_patient;
  public $send;

  public $object_id;
  public $object_class;
  public $_ref_object;

  // Derivated fields
  public $_extensioned;
  public $_no_extension;
  public $_file_size;
  public $_file_date;
  public $_icon_name;
  public $_version;

  public $_send_problem;

  public $_category_id;

  // Behavior Field
  public $_send;
  public $_created;

  /** @var CMediusers */
  public $_ref_author;

  /** @var CFilesCategory */
  public $_ref_category;

  /** @var  CDestinataireItem[] */
  public $_ref_destinataires;

  /** @var CElectronicDelivery[] */
  public $_ref_deliveries;

  /** @var CDocumentReference[] */
  public $_ref_documents_reference;

  /** @var boolean Indicate if the document has been sent by mail */
  public $_sent_mail = false;

  /** @var array The list of recipients that have received the document by mail */
  public $_mail_recipients = [];

  /** @var boolean Indicate if the document has been sent by apicrypt */
  public $_sent_apicrypt = false;

  /** @var array The list of recipients that have received the document by apicrypt */
  public $_apicrypt_recipients = [];

  /** @var boolean Indicate if the document has been sent by mssante */
  public $_sent_mssante = false;

  /** @var array The list of recipients that have received the document by mssante */
  public $_mssante_recipients = [];

  /** @var integer COunt the number of times the document has been sent */
  public $_count_deliveries = 0;

  public $_no_synchro_eai = false;

  //DMP
  public $_refs_dmp_document;
  public $_count_dmp_documents;
  public $_status_dmp;
  public $_ref_last_dmp_document;
  public $_fa_dmp;

  // Sas envoi
  public $_ref_last_file_traceability;

  // AppFine
  public $_status_appFineClient;

  // TAMM-SIH
  public $_status_sih_cabinet;
  public $_status_cabinet_sih;

  // XDS
  public $_count_xds_documents;
  public $_refs_xds_document;

  //Sisra
  public $_count_sisra_documents;
  public $_refs_sisra_document;
  public $_status_sisra;

  // FHIR
  /** @var  CIdSante400 */
  public $_ref_fhir_idex;

  // SIH
  public $_ext_cabinet_id;

  /**
   * @see parent::getProps()
   */
  function getProps() {
    $props = parent::getProps();

    $props["file_category_id"] = "ref class|CFilesCategory fieldset|default";
    $props["etat_envoi"]       = "enum notNull list|oui|non|obsolete default|non show|0 fieldset|default";
    $props["author_id"]        = "ref class|CMediusers fieldset|extra";
    $props["private"]          = "bool default|0 show|0 fieldset|default";
    $props["annule"]           = "bool default|0 show|0 fieldset|default";
    $props["doc_size"]         = "num min|0 show|0 fieldset|default";

    $props["object_id"]    = "ref notNull class|CMbObject meta|object_class cascade fieldset|extra";
    $props["object_class"] = "str notNull class show|0 fieldset|extra show|1";

    $type_doc_dmp = "";
    if (CModule::getActive("dmp")) {
      $type_doc_dmp = CDMPTools::getTypesDoc();
    }
    $props["type_doc_dmp"] = (empty($type_doc_dmp) ? "str" : "enum list|$type_doc_dmp");
    $sisra_types           = "";
    if (CModule::getActive("sisra")) {
      $sisra_types = CSisraTools::getSisraTypeDocument();
      $sisra_types = implode("|", $sisra_types);
    }
    $props["type_doc_sisra"] = (empty($sisra_types) ? "str" : "enum list|$sisra_types") . " fieldset|extra";
    $props["remis_patient"]  = "bool default|0 fieldset|default";
    $props["send"]           = "bool default|1 fieldset|default";

    $props["_extensioned"]  = "str notNull";
    $props["_no_extension"] = "str notNull";
    $props["_file_size"]    = "str show|1";
    $props["_file_date"]    = "dateTime";
    $props["_send_problem"] = "text";
    $props["_category_id"]  = "ref class|CFilesCategory";
    $props["_version"]      = "num";
    $props["_ext_cabinet_id"] = "num";

    return $props;
  }

  /**
   * @deprecated
   *
   * @param CStoredObject $object
   *
   * @return void
   */
  function setObject(CStoredObject $object) {
    CMbMetaObjectPolyfill::setObject($this, $object);
  }

  /**
   * @param bool $cache
   *
   * @return bool|CStoredObject|CExObject|null
   * @throws Exception
   */
  function loadTargetObject($cache = true) {
    return CMbMetaObjectPolyfill::loadTargetObject($this, $cache);
  }

  /**
   * Return idex type if it's special (e.g. AppFine/...)
   *
   * @param CIdSante400 $idex Idex
   *
   * @return string|null
   */
  function getSpecialIdex(CIdSante400 $idex) {
    if (CModule::getActive("appFineClient")) {
      if ($idex_type = CAppFineClient::getSpecialIdex($idex)) {
        return $idex_type;
      }
    }

    return null;
  }

  /**
   * Check status synchro file with AppFine
   *
   * @return int
   */
  function checkSynchroAppFine(CInteropReceiver $receiver = null) {
    if ($receiver) {
      // On essaye de récupérer une file_traceability pour ce document
      $last_file_traceability = $this->loadRefLastFileTraceability($receiver);
      if ($last_file_traceability->_id) {
        switch ($last_file_traceability->status) {
          case "pending":
            return $this->_status_appFineClient = 3;
            break;
          case "rejected":
            return $this->_status_appFineClient = 5;
            break;
        }
      }
    }

    return $this->_status_appFineClient = CAppFineClient::loadIdex($this)->_id ? 1 : 0;
  }

    /**
     * Check status synchro file with AppFine
     *
     * @param CInteropReceiver $receiver
     *
     * @return int
     * @throws Exception
     */
    public function checkSynchroSIHCabinet(CInteropReceiver $receiver = null): int
    {
        if ($receiver) {
            // On essaye de récupérer une file_traceability pour ce document
            $last_file_traceability = $this->loadRefLastFileTraceability($receiver);
            if ($last_file_traceability->_id) {
                switch ($last_file_traceability->status) {
                    case "pending":
                        return $this->_status_sih_cabinet = 2;
                    case "rejected":
                        return $this->_status_sih_cabinet = 3;
                    default:
                }
            }
        }

        return $this->_status_sih_cabinet = CSIHCabinet::loadIdex($this, CSIHCabinet::DOCUMENT_TAG)->_id ? 1 : 0;
    }

    /**
     * Check status synchro file with AppFine
     *
     * @param CInteropReceiver $receiver
     *
     * @return int
     * @throws Exception
     */
    public function checkSynchroCabinetSIH(CInteropReceiver $receiver = null): int
    {
        if ($receiver) {
            // On essaye de récupérer une file_traceability pour ce document
            $last_file_traceability = $this->loadRefLastFileTraceability($receiver);
            if ($last_file_traceability->_id) {
                switch ($last_file_traceability->status) {
                    case "pending":
                        return $this->_status_cabinet_sih = 2;
                    case "rejected":
                        return $this->_status_cabinet_sih = 3;
                    default:
                }
            }
        }

        return $this->_status_cabinet_sih = CCabinetSIH::loadIdex($this, CCabinetSIH::DOCUMENT_TAG)->_id ? 1 : 0;
    }

  /**
   * Count the action on the document for the DMP
   *
   * @param array $where Where
   *
   * @return int|null
   * @throws Exception
   */
  function countDocumentDMP($where = array()) {
    if (!CModule::getActive("dmp")) {
      return null;
    }

    $this->checkSynchroDMP();

    return $this->_count_dmp_documents = $this->countBackRefs("dmp_documents", $where);
  }

  /**
   * Check status synchro file with DMP
   *
   * @param CInteropReceiver $receiver receiver
   *
   * @return int|null
   * @throws Exception
   */
  function checkSynchroDMP(CInteropReceiver $receiver = null) {
    // Récupération du cdmp_document pour la version actuelle
    $dmp_document_actually = $this->loadUniqueBackRef(
      "dmp_documents", null, null, null, null, "dmp_document_actually", array("document_item_version" => " = '$this->_version' ")
    );

    if ($receiver) {
      // On essaye de récupérer une file_traceability pour ce document
      $last_file_traceability = $this->loadRefLastFileTraceability($receiver);
      if ($last_file_traceability->_id) {
        switch ($last_file_traceability->status) {
          case "pending":
            return $this->_status_dmp = 3;
          case "rejected":
            return $this->_status_dmp = 5;
        }
      }
    }

    if ($dmp_document_actually->_id) {
      return $this->_status_dmp = $dmp_document_actually->etat == "DELETE" ? 4 : 1;
    }

    // Récupération du cdmp_document pour une version antérieure
    if ($this->loadUniqueBackRef("dmp_documents", null, null, null, null, null, array())->_id) {
      return $this->_status_dmp = 2;
    }

    return $this->_status_dmp = 0;
  }

  /**
   * Get last dmp document for file
   *
   * @return CDMPDocument
   */
  function loadRefLastDMPDocument() {
    $document_dmp = new CDMPDocument();
    $document_dmp->getLastSend($this->_id, $this->_class);

    return $this->_ref_last_dmp_document = $document_dmp;
  }

  /**
   * Get last file traceability
   *
   * @param CInteropReceiver $receiver
   *
   * @return CFileTraceability
   * @throws Exception
   */
  function loadRefLastFileTraceability(CInteropReceiver $receiver = null) {
    $file_traceability = new CFileTraceability();
    $where             = array(
      "version"      => " = '$this->_version' ",
      "object_id"    => " = '$this->_id' ",
      "object_class" => " = '$this->_class' ",
    );

    if ($receiver) {
      $where["actor_class"] = " = '$receiver->_class' ";
      $where["actor_id"]    = " = '$receiver->_id'";
    }

    $files_traceability = $file_traceability->loadList($where, "created_datetime DESC", 1);

    if ($files_traceability) {
      $file_traceability = reset($files_traceability);
      $file_traceability->getMasquage();
      return $this->_ref_last_file_traceability = $file_traceability;
    }

    return $this->_ref_last_file_traceability = $file_traceability;
  }

  /**
   * Return the action on the document for the DMP
   *
   * @return CDMPDocument[]|CStoredObject[]
   * @throws Exception
   */
  function loadDocumentDMP() {
    if (!CModule::getActive("dmp")) {
      return null;
    }

    return $this->_refs_dmp_document = $this->loadBackRefs("dmp_documents", "date DESC");
  }

  /**
   * Count the action on the document for the DMP
   *
   * @param array $where Where
   *
   * @return int|null
   * @throws Exception
   */
  function countDocumentXDS($where = array()) {
    return $this->_count_xds_documents = $this->countBackRefs("xds_documents", $where);
  }

  /**
   * Return the action on the document for the DMP
   *
   * @return CXDSDocument[]|CStoredObject[]
   * @throws Exception
   */
  function loadDocumentXDS() {
    return $this->_refs_xds_document = $this->loadBackRefs("xds_documents", "date DESC");
  }

  /**
   * Count the action on the document for the DMP
   *
   * @param array $where Where
   *
   * @return int|null
   * @throws Exception
   */
  function countDocumentSisra($where = array()) {
    if (!CModule::getActive("sisra")) {
      return null;
    }

    $this->checkSynchroSisra();

    return $this->_count_sisra_documents = $this->countBackRefs("sisra_documents", $where);
  }

  function checkSynchroSisra(CInteropReceiver $receiver = null) {
    if ($receiver) {
      // On essaye de récupérer une file_traceability pour ce document
      $last_file_traceability = $this->loadRefLastFileTraceability($receiver);
      if ($last_file_traceability->_id) {
        switch ($last_file_traceability->status) {
          case "pending":
            return $this->_status_sisra = 3;
          case "rejected":
            return $this->_status_sisra = 5;
        }
      }
    }

    return $this->_status_sisra = $this->countBackRefs("sisra_documents") > 0 ? 1 : 0;
  }

  /**
   * Return the action on the document for the DMP
   *
   * @return CSisraDocument[]|CStoredObject[]
   * @throws Exception
   */
  function loadDocumentSisra() {
    if (!CModule::getActive("sisra")) {
      return null;
    }

    return $this->_refs_sisra_document = $this->loadBackRefs("sisra_documents", "date DESC");
  }

  /**
   * Load documents reference
   *
   * @return CDocumentReference[]|CStoredObject[]
   * @throws Exception
   */
  function loadDocumentsReference($where = array()) {
    return $this->_ref_documents_reference = $this->loadBackRefs("document_reference", null, null, null, null, null, null, $where);
  }

  /**
   * Load documents reference
   *
   * @param CInteropActor $actor Interop actor
   *
   * @return CDocumentReference
   * @throws Exception
   */
  function loadDocumentReferenceActor(CInteropActor $actor) {
    $where = array(
      "actor_id"    => " = '$actor->_id'",
      "actor_class" => " = '$actor->_class'"
    );

    if ($document_reference = $this->loadDocumentsReference($where)) {
      return reset($document_reference);
    }

    return new CDocumentReference();
  }

  /**
   * Retrieve content as binary data
   *
   * @return string Binary Content
   */
  function getBinaryContent() {
  }

  /**
   * Retrieve extensioned like file name
   *
   * @return string Binary Content
   */
  function getExtensioned() {
    return $this->_extensioned;
  }

  /**
   * Try and instanciate document sender according to module configuration
   *
   * @return CDocumentSender sender or null on error
   */
  static function getDocumentSender() {
    if (null == $system_sender = CAppUI::gconf("dPfiles CDocumentSender system_sender")) {
      return null;
    }

    if (!is_subclass_of($system_sender, CDocumentSender::class)) {
      trigger_error("Instanciation du Document Sender impossible.");

      return null;
    }

    return new $system_sender;
  }

  /**
   * @see parent::updateFormFields()
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_file_size = CMbString::toDecaBinary($this->doc_size);

    $this->getSendProblem();
    $this->loadRefCategory();

    self::makeIconName($this);
  }

  /**
   * Retrieve send problem user friendly message
   *
   * @return string Store-like problem message
   */
  function getSendProblem() {
    if ($sender = self::getDocumentSender()) {
      $this->_send_problem = $sender->getSendProblem($this);
    }
  }

  /**
   * @see parent::store()
   */
  function store() {
    $this->completeField("etat_envoi");
    $this->completeField("object_class");
    $this->completeField("object_id");
    $this->completeField("file_category_id");

    if ((!$this->_id && $this->file_category_id) || ($this->fieldModified('file_category_id'))) {
      $file_category = $this->loadRefCategory();
      if ($file_category->_id && $file_category->type_doc_dmp) {
        $this->type_doc_dmp = $file_category->type_doc_dmp;
      }
    }

    if ($msg = $this->handleSend()) {
      return $msg;
    }

    if ($msg = parent::store()) {
      return $msg;
    }

    if ($this->_ext_cabinet_id) {
      // If there is a cabinet id, store it as a external id
      $idex = CIdSante400::getMatch($this->_class, "cabinet_id", $this->_ext_cabinet_id, $this->_id);
      $idex->store();
    }

    return null;
  }

  /**
   * Handle document sending store behaviour
   *
   * @return string Store-like error message
   */
  function handleSend() {
    if (!$this->_send) {
      return null;
    }

    $this->_send = false;

    if (null == $sender = self::getDocumentSender()) {
      return "Document Sender not available";
    }

    switch ($this->etat_envoi) {
      case "non":
        if (!$sender->send($this)) {
          return "Erreur lors de l'envoi.";
        }
        CAppUI::setMsg("Document transmis.");
        break;
      case "oui":
        if (!$sender->cancel($this)) {
          return "Erreur lors de l'invalidation de l'envoi.";
        }
        CAppUI::setMsg("Document annulé.");
        break;
      case "obsolete":
        if (!$sender->resend($this)) {
          return "Erreur lors du renvoi.";
        }
        CAppUI::setMsg("Document annulé/transmis.");
        break;
      default:
        return "Fonction d'envoi '$this->etat_envoi' non reconnue.";
    }

    return null;
  }

  /**
   * @see parent::loadRefsFwd()
   */
  function loadRefsFwd() {
    $this->loadTargetObject();
    $this->loadRefCategory();
    $this->loadRefAuthor();
  }

  /**
   * Load category
   *
   * @return CFilesCategory
   * @throws Exception
   */
  function loadRefCategory() {
    return $this->_ref_category = $this->loadFwdRef("file_category_id", true);
  }

  /**
   * Load author
   *
   * @return CMediusers
   * @throws Exception
   */
  function loadRefAuthor() {
    return $this->_ref_author = $this->loadFwdRef("author_id", true);
  }

  /**
   * @see parent::getPerm()
   */
  function getPerm($permType) {
    $this->loadRefAuthor();
    $this->loadRefCategory();

    // Permission de base
    $perm = parent::getPerm($permType);

    // Il faut au moins avoir le droit de lecture sur la catégories
    if ($this->file_category_id) {
      $perm &= $this->_ref_category->getPerm(PERM_READ);
    }

    $curr_user = CMediusers::get();

    // Gestion de la catégorie médicale
    if ($this->_ref_category->_id && $this->_ref_category->medicale) {
      $perm = $curr_user->isProfessionnelDeSante() ||
        $curr_user->isAdmin() ||
        $curr_user->isPMSI() ||
        ($curr_user->function_id == $this->_ref_author->function_id);
    }

    // Gestion d'un document confidentiel (cabinet ou auteur)
    if ($this->private) {
      switch (CAppUI::gconf("dPcompteRendu CCompteRendu private_owner_func")) {
        default:
        case "function":
          $perm &= ($this->_ref_author->function_id === $curr_user->function_id);
          break;
        case "owner":
          $perm &= ($this->author_id === $curr_user->_id);
      }

      $perm |= $curr_user->isAdmin();
    }

    return $perm;
  }

  /**
   * Load aggregated doc item ownership
   *
   * @return array collection of arrays with docs_count, docs_weight and author_id keys
   */
  function getUsersStats() {
    return array();
  }

  /** Get all owners user IDs from aggregated owner
   *
   * @param CMbObject|null $owner Aggregated owner of class CUser|CFunctions|CGroups.
   *
   * @return null|string[] Array of user IDs, null if no owner is defined;
   */
  static function getUserIds(CMbObject $owner = null) {
    if (!$owner) {
      return null;
    }

    $user_ids = array();

    if ($owner instanceof CGroups) {
      foreach ($owner->loadBackRefs("functions") as $_function) {
        $user_ids = array_merge($user_ids, $_function->loadBackIds("users"));
      }
    }

    if ($owner instanceof CFunctions) {
      $user_ids = $owner->loadBackIds("users");
    }

    if ($owner instanceof CUser || $owner instanceof CMediusers) {
      $user_ids = array($owner->_id);
    }

    return $user_ids;
  }

  /**
   * Advanced user stats on modeles
   *
   * @param string[]|null $user_ids User IDs, null if no filter
   * @param string|null   $date_min Creation date minimal filter
   * @param string|null   $date_max Creation date maximal filter
   *
   * @return array collection of arrays with docs_count, docs_weight, object_class and category_id keys
   */
  function getUsersStatsDetails($user_ids, $date_min = null, $date_max = null) {
    return array();
  }

  /**
   * Advanced periodical stats on modeles
   *
   * @param string[]|null $user_ids     Owner user IDs, null if no filter
   * @param string|null   $object_class Document class filter
   * @param string|null   $category_id  Document category filter
   * @param int           $depth        Period count for each period types
   *
   * @return int[][] collection of arrays daily, weekly, monthly and yearly keys
   */
  function getPeriodicalStatsDetails($user_ids, $object_class = null, $category_id = null, $depth = 10) {
    $detail = array(
      "period"   => "yyyy",
      "count"    => 10,
      "weight"   => 20000,
      "date_min" => "yyyy-mm-dd hh:mm:ss",
      "date_max" => "yyyy-mm-dd hh:mm:ss",
    );

    $sample = array_fill(0, $depth, $detail);

    return array(
      "hour"  => $sample,
      "day"   => $sample,
      "week"  => $sample,
      "month" => $sample,
      "year"  => $sample,
    );
  }

  /**
   * Disk usage of a user
   *
   * @param string $user_id User id, connected user by default
   *
   * @return array collection of arrays with total usage, last year usage and last month usage
   */
  function getDiskUsage($user_id) {
    return array();
  }

  /**
   * Return the patient
   *
   * @return CPatient|null
   */
  function loadRelPatient() {
    /** @var CPatient|IPatientRelated $object */
    $object = $this->loadTargetObject();
    if ($object instanceof CPatient) {
      return $object;
    }
    if (in_array(IPatientRelated::class, class_implements($object))) {
      return $object->loadRelPatient();
    }

    return null;
  }

  /**
   * @param string $sender   The sender's email address
   * @param string $receiver The receiver's email address
   *
   * @return string
   */
  function makeHprimHeader($sender, $receiver) {
    $object   = $this->loadTargetObject();
    $receiver = explode('@', $receiver);
    $sender   = explode('@', $sender);

    /* Handle the case when the file is generated by Mediboard */
    if ($object->_class == 'CCompteRendu') {
      $object = $object->loadTargetObject();
    }

    $patient     = null;
    $record_id   = null;
    $record_date = null;
    switch ($object->_class) {
      case 'CConsultation':
        /** @var $object CConsultation */
        $patient = $object->loadRefPatient();
        $object->loadRefSejour();
        if ($object->_ref_sejour) {
          $object->_ref_sejour->loadNDA();
          $record_id = $object->_ref_sejour->_NDA;
        }
        $object->loadRefPlageConsult();
        $record_date = $object->_ref_plageconsult->getFormattedValue('date');
        break;
      case 'CConsultAnesth':
        /** @var $object CConsultAnesth */
        $patient = $object->loadRefPatient();
        $object->loadRefSejour();
        if ($object->_ref_sejour) {
          $object->_ref_sejour->loadNDA();
          $record_id = $object->_ref_sejour->_NDA;
        }
        $object->loadRefConsultation();
        $object->_ref_consultation->loadRefPlageConsult();
        $record_date = $object->_ref_consultation->_ref_plageconsult->getFormattedValue('date');
        break;
      case 'CSejour':
        /** @var $object CSejour */
        $patient = $object->loadRefPatient();
        $object->loadNDA();
        $record_id = $object->_NDA;
        $object->updateFormFields();
        $record_date = $object->getFormattedValue('_date_entree');
        break;
      case 'COperation':
        /** @var $object COperation */
        $patient = $object->loadRefPatient();
        $object->loadRefSejour();
        if ($object->_ref_sejour) {
          $object->_ref_sejour->loadNDA();
          $record_id = $object->_ref_sejour->_NDA;
        }

        /* Récupération de la date */
        if ($object->date) {
          $record_date = $object->getFormattedValue('date');
        }
        else {
          $object->loadRefPlageOp();
          $record_date = $object->_ref_plageop->getFormattedValue('date');
        }
        break;
      case 'CPatient':
        $patient = $object;
        break;
      default:
        $patient = new CPatient();
    }

    $patient->loadIPP();
    $adresse = explode("\n", $patient->adresse);

    if (count($adresse) == 1) {
      $adresse[1] = "";
    }
    elseif (count($adresse) > 2) {
      $adr_tmp = $adresse;
      $adresse = array($adr_tmp[0]);
      unset($adr_tmp[0]);
      $adr_tmp   = implode(" ", $adr_tmp);
      $adresse[] = str_replace(array("\n", "\r"), array("", ""), $adr_tmp);
    }

    return $patient->_IPP . "\n"
      . strtoupper($patient->nom) . "\n"
      . ucfirst($patient->prenom) . "\n"
      . $adresse[0] . "\n"
      . $adresse[1] . "\n"
      . $patient->cp . " " . $patient->ville . "\n"
      . $patient->getFormattedValue("naissance") . "\n"
      . $patient->matricule . "\n"
      . $record_id . "\n"
      . $record_date . "\n"
      . ".          $sender[0]\n"
      . ".          $receiver[0]\n\n";
  }

  static function makeIconName($object) {
    switch ($object->_class) {
      default:
      case "CCompteRendu":
        $file_name = $object->nom;
        break;
      case "CFile":
        $file_name = $object->file_name;
        break;
      case "CExClass":
        $file_name = $object->name;
    }

    $max_length = 25;

    if (strlen($file_name) <= $max_length) {
      return $object->_icon_name = $file_name;
    }

    return $object->_icon_name = substr_replace($file_name, " ... ", $max_length / 2, round(-$max_length / 2));
  }

  /**
   * Count the receivers
   *
   * @return CDestinataireItem[]
   */
  function loadRefsDestinataires() {
    return $this->_ref_destinataires = $this->loadBackRefs("destinataires");
  }

  /**
   * Load the CElectronicDelivery
   *
   * @return CElectronicDelivery[]
   */
  public function loadRefDeliveries() {
    $this->_ref_deliveries = $this->loadBackRefs('deliveries');

    if (is_array($this->_ref_deliveries)) {
      $this->_count_deliveries = count($this->_ref_deliveries);
    }

    return $this->_ref_deliveries;
  }

    /**
     * Check the electronic delivery status of the document
     */
    public function getDeliveryStatus(): void
    {
        if (!CModule::getActive("messagerie")) {
            return;
        }

        $this->_mail_recipients = CElectronicDelivery::getMailRecipients($this);
        $this->_sent_mail       = (bool)(count($this->_mail_recipients) > 0);

        $this->_apicrypt_recipients = CElectronicDelivery::getApicryptRecipients($this);
        $this->_sent_apicrypt       = (bool)(count($this->_apicrypt_recipients) > 0);

        $this->_mssante_recipients = CElectronicDelivery::getMssanteRecipients($this);
        $this->_sent_mssante       = (bool)(count($this->_mssante_recipients) > 0);
    }

  /**
   * Load files for on object
   *
   * @param CMbObject $object         object to load the files
   * @param string    $order          order to sort the files (nom/date)
   * @param boolean   $with_cancelled include cancelled files
   *
   * @return array[][]
   */
  static function loadDocItemsByObject(CMbObject $object, $order = "nom", $with_cancelled = true) {
    $where = array();

    if (!$with_cancelled) {
      $where["annule"] = "= '0'";
    }

    if (!$object->_ref_files) {
      $object->loadRefsFiles($where);
    }
    if (!$object->_ref_documents) {
      $object->loadRefsDocs($where);
    }

    // Création du tableau des catégories pour l'affichage
    $affichageFile = array(
      array(
        "name"  => CAppUI::tr("CFilesCategory.none"),
        "items" => array(),
      )
    );

    foreach (CFilesCategory::listCatClass($object->_class) as $_cat) {
      $affichageFile[$_cat->_id] = array(
        "name"  => $_cat->nom,
        "items" => array(),
      );
    }

    $order_by = array(
      "CFile"        => $order == "date" ? "file_date" : "file_name",
      "CCompteRendu" => $order == "date" ? "creation_date" : "nom"
    );

    // Ajout des fichiers dans le tableau
    foreach ($object->_ref_files as $_file) {
      $cat_id                                                                                  = $_file->file_category_id ?: 0;
      $affichageFile[$cat_id]["items"][$_file->{$order_by[$_file->_class]} . "-$_file->_guid"] = $_file;
      if (!isset($affichageFile[$cat_id]["name"])) {
        $affichageFile[$cat_id]["name"] = $cat_id ? $_file->_ref_category->nom : "";
      }
    }

    // Ajout des document dans le tableau
    foreach ($object->_ref_documents as $_doc) {
      $_doc->isLocked();
      $cat_id                                                                               = $_doc->file_category_id ?: 0;
      $affichageFile[$cat_id]["items"][$_doc->{$order_by[$_doc->_class]} . "-$_doc->_guid"] = $_doc;
      if (!isset($affichageFile[$cat_id]["name"])) {
        $affichageFile[$cat_id]["name"] = $cat_id ? $_doc->_ref_category->nom : "";
      }
    }

    // Classement des Fichiers et des document par Ordre alphabétique
    foreach ($affichageFile as $keyFile => $currFile) {
      switch ($order) {
        default:
        case "nom":
          ksort($affichageFile[$keyFile]["items"]);
          break;
        case "date":
          krsort($affichageFile[$keyFile]["items"]);
      }
    }

    return $affichageFile;
  }

  /**
   * @inheritdoc
   */
  public function loadView() {
    parent::loadView();

    $this->loadRefDeliveries();
    if (is_array($this->_ref_deliveries)) {
      foreach ($this->_ref_deliveries as $delivery) {
        $delivery->loadRefMessage();
      }
    }

    $this->countSynchronizedRecipients();
  }

  /**
   * Loading recipients synchronized with the document
   *
   * @return void
   * @throws Exception
   */
    public function countSynchronizedRecipients(): void
    {
        if (CModule::getActive('appFineClient')) {
            $this->checkSynchroAppFine();
        }

        if (CModule::getActive('dmp')) {
            $this->countDocumentDMP();
        }

        if (CModule::getActive('xds')) {
            $this->countDocumentXDS();
        }

        if (CModule::getActive('sisra')) {
            $this->countDocumentSisra();
        }

        if (CModule::getActive('oxSIHCabinet')) {
            $this->checkSynchroSIHCabinet();
        }

        if (CModule::getActive('oxCabinetSIH')) {
            $this->checkSynchroCabinetSIH();
        }
    }

  /**
   * Retourne les destinataires possibles pour un objet
   *
   * @param CPatient|CConsultation|CSejour $object       Objet concerné
   * @param string                         $address_type The type of address to use (mail, mssante or apicrypt)
   *
   * @return CDestinataire[]
   */
  static function getDestinatairesCourrier($object, $address_type = 'mail') {
    $destinataires = array();

    /* In case of a CPrescription, get the linked object instead */
    if ($object instanceof CPrescription) {
      $object = $object->loadRefObject();
    }

    if (!in_array($object->_class, array("COperation", "CConsultation", "CPatient", "CSejour", 'CConsultAnesth'))) {
      return $destinataires;
    }

    CDestinataire::makeAllFor($object, null, $address_type);

    /** @var CDestinataire[] $_destinataires_by_class */
    foreach (CDestinataire::$destByClass as $_destinataires_by_class) {
      foreach ($_destinataires_by_class as $_destinataire) {
        if (!isset($_destinataire->nom) || strlen($_destinataire->nom) == 0 || $_destinataire->nom === " "
          || (!$_destinataire->email && $address_type != 'apicrypt')
        ) {
          continue;
        }

        $destinataires[] = $_destinataire;
      }
    }

    return $destinataires;
  }

  /**
   * Returns an array of all dmp document types
   *
   * @return array
   * @throws Exception
   */
  public static function getDmpTypeDocs() {
    $jdv_type = CInteropResources::loadJV(CMbArray::get(CDMPValueSet::$JDV, "typeCode"), CDMPValueSet::$type, false);

    $type_docs = [];
    foreach ($jdv_type as $_type) {
      $code             = CMbArray::get($_type, "codeSystem") . "^" . CMbArray::get($_type, "code");
      $disp_name        = CMbArray::get($_type, "displayName");
      $type_docs[$code] = $disp_name;
    }

    return $type_docs;
  }

  /**
   * Returns the name to display for a dmp type document code
   *
   * @param string $code - dmp document type code
   *
   * @return mixed
   * @throws Exception
   */
  public static function getDisplayNameDmp($code) {
    $types = self::getDmpTypeDocs();

    return CMbArray::get($types, $code);
  }
}
