<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\Patients;

use DirectoryIterator;
use DOMElement;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Import\CExternalDBImport;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\Import\CMbXMLObjectImport;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Bloc\CPlageOp;
use Ox\Mediboard\Cabinet\CBanque;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Ccam\CActe;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Facturation\CFactureEtablissement;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CCategoryPrescription;
use Ox\Mediboard\Prescription\CElementPrescription;
use Ox\Mediboard\Mpm\CMomentUnitaire;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Prescription\CPrescriptionLineElement;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Ox\Mediboard\Mpm\CPrisePosologie;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CContentHTML;

/**
 * Utility class for importing objects
 */
class CPatientXMLImport extends CMbXMLObjectImport {
  protected $name_suffix;

  protected $error_count = 0;

  protected $imported = array();

  protected $import_order = array(
    // Structure objects
    "//object[@class='CGroups']",
    "//object[@class='CMediusers']",
    "//object[@class='CUser']",
    "//object[@class='CService']",
    "//object[@class='CFunctions']",
    "//object[@class='CBlocOperatoire']",
    "//object[@class='CSalle']",

    "//object[@class='CMedecin']",
    "//object[@class='CPatient']",
    "//object[@class='CDossierMedical']",
    "//object[@class='CSejour']",
    "//object[@class='CPlageOp']",
    "//object[@class='COperation']",
    "//object[@class='CConsultation']",
    "//object[@class='CConstanteMedicale']",
    "//object[@class='CFile']",
    "//object[@class='CCompteRendu']",

    // Import prescriptions
    "//object[@class='CMomentUnitaire']",
    "//object[@class='CCategoryPrescription']",
    "//object[@class='CPrescription']",
    "//object[@class='CPrescriptionLineMedicament']",
    "//object[@class='CPrescriptionLineElement']",
    "//object[@class='CPrisePosologie']",
    // Fin import prescriptions

    // Other objects
    "//object",
  );

  protected $directory;

  protected $files_directory;

  protected $update_data = false;
  protected $update_patient = false;
  protected $patient_name = "";

  static $_ignored_classes = array(
    // Structure
    "CGroups",
    "CMediusers",
    "CUser",
    "CService",
    "CFunctions",
    "CBlocOperatoire",
    "CSalle",
    "CUniteFonctionnelle",
  );

  static $_prescription_classes = array(
    "CPrescription",
    "CPrescriptionLineMedicament",
    "CPrescriptionLineComment",
    "CPrescriptionLineElement",
    "CMomentUnitaire",
    "CPrisePosologie",
    "CElementPrescription",
    "CCategoryPrescription",
  );

  protected $_tmp_ignored_classes = array();

  /**
   * @inheritdoc
   */
  function importObject(DOMElement $element) {
    if (!$element) {
      return;
    }

    $id = $element->getAttribute("id");

    // Avoid importing the same object multiple time from a single XML file
    if (isset($this->imported[$id])) {
      return;
    }

    $this->name_suffix = " (import du " . CMbDT::dateTime() . ")";

    $_class          = $element->getAttribute("class");
    $imported_object = null;

    $import_tag = $this->getImportTag();

    // Check if the object has been imported
    $idex   = self::lookupObject($id, $import_tag);
    $object = null;
    if ($idex->_id) {
      $this->imported[$id] = true;
      $object              = $idex->loadTargetObject();
      $this->map[$id]      = $object->_guid;

      if (!$this->update_data) {
        if (!isset($this->options['link_file_to_op']) || !$this->options['link_file_to_op']
          || ($_class != "CContentHTML" && $_class != "CCompteRendu")
        ) {
          return;
        }
      }
    }

    if ($this->isIgnored($_class)) {
      return;
    }

    switch ($_class) {
      // COperation = Intervention: Données incorrectes, Le code CCAM 'QZEA024 + R + J' n'est pas valide
      case "CPatient":
        $imported_object = $this->importPatient($element, $object);
        break;

      case "CDossierMedical":
        $imported_object = $this->importDossierMedical($element, $object);
        break;

      case "CAntecedent":
        $imported_object = $this->importAntecedent($element, $object);
        break;

      case "CPlageOp":
      case "CPlageconsult":
        $imported_object = $this->importPlage($element, $object);
        break;

      case "CFile":
        $imported_object = $this->importFile($element, $object);
        break;

      case "CCompteRendu":
        $imported_object = $this->importCompteRendu($element, $object);
        break;

      case "CConsultation":
        $imported_object = $this->importConsultation($element, $object);
        break;

      case "CSejour":
        $imported_object = $this->importSejour($element, $object);
        break;

      case "COperation":
        $imported_object = $this->importOperation($element, $object, $import_tag);
        break;

      case "CContentHTML":
        $imported_object = $this->importContentHTML($element, $object);
        break;

      case "CBanque":
        $imported_object = $this->importBanque($element, $object);
        break;

      case "CMedecin":
        $imported_object = $this->importMedecin($element, $object);
        break;

      case "CFactureEtablissement":
        $imported_object = $this->importFactureEtablissement($element, $object);
        break;

      case "CPrescription":
        $imported_object = $this->importPrescription($element, $object);
        break;
      case "CPrescriptionLineMedicament":
        $imported_object = $this->importPrescriptionLineMedicament($element, $object);
        break;
      case "CPrisePosologie":
        $imported_object = $this->importPrisePosologie($element, $object);
        break;
      case "CMomentUnitaire":
        $imported_object = $this->importMomentUnitaire($element, $object);
        break;
      case "CPrescriptionLineElement":
        $imported_object = $this->importPrescriptionLineElement($element, $object);
        break;
      case "CElementPrescription":
        $imported_object = $this->importElementPrescription($element, $object);
        break;
      case "CCategoryPrescription":
        $imported_object = $this->importCategoryPrescription($element, $object);
        break;
      case "CIdSante400":
        $imported_object = $this->importExternalId($element, $object);
        break;

      default:
        // Ignored classes
        if (!class_exists($_class) || $this->isIgnored($_class)) {
          break;
        }

        $_object = $this->getObjectFromElement($element, $object);

        if ($_object instanceof CActe) {
          $_object->_check_coded = false;
        }

        $_object->loadMatchingObjectEsc();

        if (!$this->storeObject($_object, $element)) {
          break;
        }

        $imported_object = $_object;
        break;
    }

    // Store idex on new object
    if ($imported_object && $imported_object->_id) {
      // Do not search external id on CIdSante400 objects
      if ($imported_object instanceof CMbObject && !($imported_object instanceof CIdSante400)) {
        $idex->setObject($imported_object);
        $idex->id400 = $id;
        if ($msg = $idex->store()) {
          CAppUI::stepAjax($msg, UI_MSG_WARNING);
        }
      }
    }
    else {
      if (!$this->isIgnored($_class)) {
        $this->error_count++;
        $this->writeLog("$id sans objet", null, UI_MSG_WARNING);
      }
    }

    if ($imported_object) {
      $this->map[$id] = $imported_object->_guid;
    }

    $this->imported[$id] = true;
  }

  /**
   * @param DOMElement $element The DOM element to parse
   *
   * @return string
   */
  function getCFileDirectory(DOMElement $element) {
    list($object_class, $object_id) = explode("-", $element->getAttribute("object_id"));
    $uid = $this->getNamedValueFromElement($element, "file_real_filename");
    if ($this->files_directory) {
      $basedir = rtrim($this->files_directory, "/\\");
    }
    else {
      $basedir = rtrim($this->directory, "/\\");
    }

    $dir = $basedir . "/$object_class/" . intval($object_id / 1000) . "/$object_id/$uid";

    if (!is_dir($dir)) {
      $dir = $basedir . "/$object_class/$object_id/$uid";
    }

    return $dir;
  }

  /**
   * @inheritdoc
   */
  function importObjectByGuid($guid) {
    list($class, $id) = explode("-", $guid);

    if ($this->isIgnored($class)) {
      $lookup_guid = $guid;

      if ($class == "CMediusers") {
        // Idex are stored on the CUser
        $lookup_guid = "CUser-$id";
      }

      $import_tag = $this->getImportTag();
      $idex       = $this->lookupObject($lookup_guid, $import_tag);

      if ($idex->_id) {
        $this->map[$guid]      = "$class-$idex->object_id";
        $this->imported[$guid] = true;
      }
      else {
        if ($class == "CMediusers") {
          $this->map[$guid]      = CMediusers::get()->_guid;
          $this->imported[$guid] = true;
        }
      }
    }
    else {
      /** @var DOMElement $_element */
      $_element = $this->xpath->query("//*[@id='$guid']")->item(0);
      if ($_element) {
        $this->importObject($_element);
      }
    }
  }

  /**
   * Set the files directory
   *
   * @param string $directory Files directory path
   *
   * @return void
   */
  function setFilesDirectory($directory) {
    $this->files_directory = $directory;
  }

  /**
   * @param string $directory    Directory path
   * @param string $patient_name Patient identifier
   *
   * @return void
   */
  function setDirectory($directory, $patient_name = "") {
    $this->directory    = $directory;
    $this->patient_name = $patient_name;
  }

  /**
   * @param bool $update_data    Update existing datas or not
   * @param bool $update_patient Update the patient infos
   *
   * @return void
   */
  function setUpdateData($update_data, $update_patient = false) {
    $this->update_data    = $update_data;
    $this->update_patient = $update_patient;
  }

  /**
   * Try to get a consult for the file
   *
   * @param string $date       Date of the file
   * @param int    $patient_id Patient id
   * @param int    $author_id  Author id
   *
   * @return CConsultation
   */
  function getConsultForFile($date, $patient_id, $author_id) {
    $ds           = CSQLDataSource::get('std');
    $date         = CMbDT::date($date);
    $consultation = new CConsultation();
    $ljoin        = array("plageconsult" => "consultation.plageconsult_id = plageconsult.plageconsult_id",);
    $where        = array(
      "consultation.patient_id" => $ds->prepare('= ?', $patient_id),
      "plageconsult.chir_id"    => $ds->prepare('= ?', $author_id),
      "plageconsult.date"       => $ds->prepare('BETWEEN ?1 AND ?2', CMbDT::date('-1 DAY', $date), CMbDT::date('+1 DAY', $date)),
    );

    $consultation->loadObject($where, null, null, $ljoin);

    return $consultation;
  }

  /**
   * Try to get a sejour for the file
   *
   * @param string $date       Date of the file
   * @param int    $patient_id File patient_id
   *
   * @return CSejour
   */
  function getSejourForFile($date, $patient_id) {
    $ds               = CSQLDataSource::get('std');
    $file_date_entree = CMbDT::dateTime("+1 DAY", $date);
    $file_date_sortie = CMbDT::dateTime("-1 DAY", $date);
    $sejour           = new CSejour();
    $where            = array(
      'entree'     => $ds->prepare('< ?', $file_date_entree),
      'sortie'     => $ds->prepare('> ?', $file_date_sortie),
      'patient_id' => $ds->prepare('= ?', $patient_id),
    );

    $sejour->loadObject($where);

    return $sejour;
  }

  /**
   * Try to get an operation for the file
   *
   * @param string  $date   Date of the file
   * @param CSejour $sejour Sejour of the CFile
   *
   * @return COperation
   */
  function getOperationForFile($date, $sejour) {
    $file_date = CMbDT::date($date);
    $ds        = CSQLDataSource::get('std');

    $where      = array(
      'date' => $ds->prepare('BETWEEN ?1 AND ?2', CMbDT::date('-1 DAY', $file_date), CMbDT::date('+1 DAY', $file_date))
    );
    $operations = $sejour->loadRefsOperations($where);

    return reset($operations);
  }

  /**
   * Import a CPatient from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CMbObject|CPatient|null
   */
  function importPatient($element, $object) {
    /** @var CPatient $_patient */
    $_patient = $this->getObjectFromElement($element, $object);

    // Remove first or last space from nom/prenom
    // Remove double spaces which won't allow the matching to be done
    $_patient->nom    = trim(preg_replace('/\s+/', ' ', $_patient->nom));
    $_patient->prenom = trim(preg_replace('/\s+/', ' ', $_patient->prenom));

    if ($_patient->naissance == "0000-00-00") {
      $_patient->naissance = "1850-01-01";
    }

    $_ipp = null;
    // If the ipp_tag option is specified use it to search the patient by IPP
    if (isset($this->options['ipp_tag'])) {
      $_ipp = $this->searchIPP($this->options['ipp_tag']);
      if ($_ipp) {
        $_patient->_IPP = $_ipp;
        $_patient->loadFromIPP();
      }
    }

    $duplicates = 0;
    if (!$_patient->_id) {
      $where = array();
      // If the patient matching have to be done on more fields
      if (isset($this->options['additionnal_pat_matching'])) {
        foreach ($this->options['additionnal_pat_matching'] as $_field) {
          $where[] = $_field;
        }
      }

      // Add additionnal fields matching and trim data in database for comparison
      $duplicates = $_patient->loadMatchingPatient(false, true, $where, true);
    }

    // If we don't want to update patients with duplicates return null;
    if (!$_patient->_id && isset($this->options["exclude_duplicate"]) && $this->options['exclude_duplicate'] && $duplicates > 0) {
      $this->setStop(true);
      $this->writeLog(CAppUI::tr("dPpatients-imports-duplicate exists"), $element, UI_MSG_WARNING);

      return null;
    }

    // Avoid updating patients that already exists
    if (isset($this->options['no_update_patients_exists']) && $this->options['no_update_patients_exists']
      && $_patient && $_patient->_id
    ) {
      $sejours = $_patient->loadRefsSejours();
      $consuls = $_patient->loadRefsConsultations();

      if ($sejours || $consuls) {
        if (isset($this->options['exclude_duplicate']) && $this->options['exclude_duplicate']) {
          $this->setStop(true);
          $this->writeLog(CAppUI::tr("dPpatients-imports-sejour or consult exists"), $element, UI_MSG_WARNING);

          return null;
        }

        $this->_tmp_ignored_classes = array_merge(
          CPatientXMLImport::$_prescription_classes,
          array(
            "CDossierMedical", "CAntecedent", "CTraitement"
          )
        );

        $this->update_patient = false;
        $this->update_data    = false;
      }
    }

    if ($this->update_patient || !$_patient->_id) {
      $_patient = $this->getObjectFromElement($element, $_patient);

      $_patient->nom    = trim(preg_replace('/\s+/', ' ', $_patient->nom));
      $_patient->prenom = trim(preg_replace('/\s+/', ' ', $_patient->prenom));

      $is_new = !$_patient->_id;

      if ($is_new) {
        CAppUI::stepAjax("Patient '%s' créé", UI_MSG_OK, $_patient->_view);
      }
      else {
        CAppUI::stepAjax("Patient '%s' retrouvé", UI_MSG_OK, $_patient->_view);
      }

      if ($msg = $_patient->store()) {
        $this->writeLog($msg, $element, UI_MSG_WARNING);
        $this->setStop(true);

        return null;
      }
    }
    else {
      CAppUI::stepAjax("Patient '%s' retrouvé", UI_MSG_OK, $_patient->_view);
    }

    if ($_ipp && $_patient && $_patient->_id && !$_patient->loadIPP()) {
      $_tag       = $_patient->getTagIpp();
      $ipp        = new CIdSante400();
      $ipp->tag   = $_tag;
      $ipp->id400 = $_ipp;
      $ipp->setObject($_patient);

      // Load matching tag to avoid creating multiple IPP
      $ipp->loadMatchingObject();

      if (!$ipp->_id) {
        if ($msg = $ipp->store()) {
          $this->writeLog($msg, $element, UI_MSG_WARNING);
        }
      }
    }

    return $_patient;
  }

  /**
   * Search an IPP in the XML file
   *
   * @param string $ipp_tag Tag IPP to use for the research
   *
   * @return string
   */
  protected function searchIPP($ipp_tag) {
    $ipp = "";

    if ($ipp_tag) {
      // Récupération du noeud contenant la valeur de l'IPP du patient dans sa base d'origine
      $xpath = "//object[@class='CIdSante400'][field[@name='tag'] = '$ipp_tag']/field[@name='id400']";
      $node  = $this->xpath->query($xpath);

      if ($node->length > 0) {
        $ipp = $node->item(0)->nodeValue;
      }
    }

    return $ipp;
  }

  /**
   * Import a CDossierMedical from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CDossierMedical|CMbObject|null
   */
  function importDossierMedical($element, $object) {
    /** @var CDossierMedical $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $_dossier               = new CDossierMedical();
    $_dossier->object_id    = $_object->object_id;
    $_dossier->object_class = $_object->object_class;

    $_dossier->loadMatchingObjectEsc();

    if (!$_dossier->_id) {
        if ($_object->risque_MCJ_patient == 'sans') {
            $_object->risque_MCJ_patient = 'aucun';
        }

        // Compatibility with old branches
        $_object->repair();

      if (!$this->storeObject($_object, $element)) {
        return null;
      }

      return $_object;
    }

    return $_dossier;
  }

  /**
   * Import a CAntecedent from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CAntecedent|CMbObject|null
   */
  function importAntecedent($element, $object) {
    /** @var CAntecedent $_new_atcd */
    $_new_atcd = $this->getObjectFromElement($element, $object);

    // On cherche un ATCD similaire
    $_empty_atcd                     = new CAntecedent();
    $_empty_atcd->dossier_medical_id = $_new_atcd->dossier_medical_id;
    $_empty_atcd->type               = $_new_atcd->type ?: null;
    $_empty_atcd->appareil           = $_new_atcd->appareil ?: null;
    $_empty_atcd->annule             = $_new_atcd->annule ?: null;
    $_empty_atcd->date               = $_new_atcd->date ?: null;
    $_empty_atcd->rques              = $_new_atcd->rques ?: null;
    $_empty_atcd->loadMatchingObjectEsc();

    if (!$_empty_atcd->_id) {
      $_new_atcd->_forwardRefMerging = true; // To accept any ATCD type
      if (!$this->storeObject($_new_atcd, $element)) {
        return null;
      }
    }

    return $_new_atcd;
  }

  /**
   * Import a CPlageConsult or CPlageOp from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CMbObject|CPlageconsult|CPlageOp|mixed|null
   */
  function importPlage($element, $object) {
    /** @var CPlageOp|CPlageconsult $_plage */
    $_plage = $this->getObjectFromElement($element, $object);

    if (($this->options['date_min'] && $_plage->date < $this->options['date_min'])
      || ($this->options['date_max'] && $_plage->date > $this->options['date_max'])
    ) {
      return null;
    }

    $_plage->hasCollisions();

    if (count($_plage->_colliding_plages)) {
      $_plage = reset($_plage->_colliding_plages);
      CAppUI::stepAjax("%s '%s' retrouvée", UI_MSG_OK, CAppUI::tr($_plage->_class), $_plage->_view);
    }
    else {
      if (!$this->storeObject($_plage, $element)) {
        return null;
      }
    }

    return $_plage;
  }

  /**
   * Import a CFile from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CFile|CMbObject|null
   */
  function importFile($element, $object) {
    /** @var CFile $_file */
    $_file    = $this->getObjectFromElement($element, $object);

      if (($this->options['date_min'] && $_file->file_date < $this->options['date_min'])
          || ($this->options['date_max'] && $_file->file_date > $this->options['date_max'])
      ) {
          return null;
      }

    $_filedir = $this->getCFileDirectory($element);

    if ($msg = $_file->check()) {
      $this->writeLog($msg, $element, UI_MSG_WARNING);

      return null;
    }

    if (isset($this->options['link_file_to_op']) && $this->options['link_file_to_op'] && $_file->object_class == 'CPatient') {
      $sejour = $this->getSejourForFile($_file->file_date, $_file->object_id);
      if ($sejour && $sejour->_id) {
        $operation = $this->getOperationForFile($_file->file_date, $sejour);
        if ($operation && $operation->_id) {
          $_file->object_class = 'COperation';
          $_file->object_id    = $operation->_id;
        }
        else {
          $_file->object_class = 'CSejour';
          $_file->object_id    = $sejour->_id;
        }
      }
      else {
        $consult = $this->getConsultForFile($_file->file_date, $_file->object_id, $_file->author_id);
        if (!$consult || !$consult->_id) {
          $consult = CExternalDBImport::makeConsult($_file->object_id, $_file->author_id, $_file->file_date);
        }

        if ($consult && $consult->_id) {
          $_file->object_class = 'CConsultation';
          $_file->object_id    = $consult->_id;
        }
      }
    }

    $tmp_file               = new CFile();
    $tmp_file->object_class = $_file->object_class;
    $tmp_file->object_id    = $_file->object_id;
    $tmp_file->file_name    = $_file->file_name;
    $tmp_file->author_id    = $_file->author_id;
    $tmp_file->loadMatchingObjectEsc();

    if ($tmp_file && $tmp_file->_id) {
      if (!$this->update_data) {
        return $tmp_file;
      }

      $_file = $tmp_file;
    }

    $correct_files = isset($this->options['correct_file']) ? $this->options['correct_file'] : false;
    if (CExternalDBImport::isBadFileDate($_file->file_date, $_file->file_name, $correct_files, 'dPpatients')) {
      return null;
    }

    if (CAppUI::conf('dPfiles CFile prefix_format')) {
      $prefix = $_file->getPrefix($_file->file_date);
      if (strpos($_file->file_real_filename, $prefix) !== 0) {
        $_file->file_real_filename = $prefix . $_file->file_real_filename;
      }
    }

    if (CAppUI::gconf('importTools import copy_files')) {
      $_file->setCopyFrom($_filedir);
    }
    else {
      $_file->setMoveFrom($_filedir);
    }


    if (!$this->storeObject($_file, $element)) {
      return null;
    }

    return $_file;
  }

  /**
   * Import a CCompteRendu from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CCompteRendu|CMbObject|null
   */
  function importCompteRendu($element, $object) {
      CCompteRendu::$import = true;

    /** @var CCompteRendu $cr */
    $cr = $this->getObjectFromElement($element, $object);

      if (($this->options['date_min'] && $cr->creation_date < $this->options['date_min'])
          || ($this->options['date_max'] && $cr->creation_date > $this->options['date_max'])
      ) {
          return null;
      }

    if (isset($this->options['link_file_to_op']) && $this->options['link_file_to_op'] && $cr->object_class == 'CPatient') {
      $sejour = $this->getSejourForFile($cr->creation_date, $cr->object_id);
      if ($sejour && $sejour->_id) {
        $operation = $this->getOperationForFile($cr->creation_date, $sejour);
        if ($operation && $operation->_id) {
          $cr->object_class = 'COperation';
          $cr->object_id    = $operation->_id;
        }
        else {
          $cr->object_class = 'CSejour';
          $cr->object_id    = $sejour->_id;
        }
      }
      else {
        $consult = $this->getConsultForFile($cr->creation_date, $cr->object_id, $cr->author_id);
        if ($consult && $consult->_id) {
          $cr->object_class = 'CConsultation';
          $cr->object_id    = $consult->_id;
        }
      }
    }

    $tmp_cr               = new CCompteRendu();
    $tmp_cr->object_class = $cr->object_class;
    $tmp_cr->object_id    = $cr->object_id;
    $tmp_cr->nom          = $cr->nom;
    $tmp_cr->author_id    = $cr->author_id;
    $tmp_cr->loadMatchingObjectEsc();

    if ($tmp_cr && $tmp_cr->_id) {
      if (!$this->update_data) {
        return $tmp_cr;
      }

        $cr = $this->getObjectFromElement($element, $tmp_cr);
    }

    if ($cr->font == 'calibri') {
      $cr->font = 'carlito';
    }

    if (!$this->storeObject($cr, $element)) {
      return null;
    }

    return $cr;
  }

  /**
   * Import a CConsultation from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CConsultation|CMbObject|null
   */
  function importConsultation($element, $object) {
    /** @var CConsultation $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $_new_consult                  = new CConsultation();
    $_new_consult->patient_id      = $_object->patient_id;
    $_new_consult->plageconsult_id = $_object->plageconsult_id;
    $_new_consult->loadMatchingObjectEsc();

    if ($_new_consult->_id) {
      $_object = $_new_consult;

      if (!$this->update_data) {
        CAppUI::stepAjax(CAppUI::tr($_object->_class) . " '%s' retrouvée", UI_MSG_OK, $_object);
      }
      else {
        $_object = $this->getObjectFromElement($element, $_object);
        $_object->_is_importing = true;

        if (!$this->storeObject($_object, $element)) {
          return null;
        }
      }
    }
    else {
      if (!$this->storeObject($_object, $element)) {
        return null;
      }
    }

    return $_object;
  }

  /**
   * Import a CSejour from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CMbObject|CSejour|mixed|null
   */
  function importSejour($element, $object) {
    /** @var CSejour $_object */
    $_object = $this->getObjectFromElement($element, $object);

    if (($this->options['date_min'] && $_object->entree < $this->options['date_min'])
        || ($this->options['date_max'] && $_object->entree > $this->options['date_max'])
    ) {
      return null;
    }

    $_sej = $this->findSejour($_object->patient_id, $_object->entree, $_object->type, $_object->praticien_id, $_object->annule);
    if ($_sej && $_sej->_id) {
      return $_sej;
    }

    $_collisions = $_object->getCollisions();

    if (count($_collisions)) {
      $_object = reset($_collisions);

      CAppUI::stepAjax(CAppUI::tr($_object->_class) . " '%s' retrouvé", UI_MSG_OK, $_object);
    }
    else {
      $_object->_hour_entree_prevue = null;
      $_object->_min_entree_prevue  = null;
      $_object->_hour_sortie_prevue = null;
      $_object->_min_sortie_prevue  = null;

      if (isset($this->options['uf_replace']) && $this->options['uf_replace']) {
        $_object->uf_medicale_id = $this->options['uf_replace'];
      }

      if (!$this->storeObject($_object, $element)) {
        return null;
      }
    }

    return $_object;
  }

  /**
   * Import a COperation from a XML element
   *
   * @param DOMElement $element    XML element
   * @param CMbObject  $object     Object found
   * @param string     $import_tag Tag used for the import
   *
   * @return CMbObject|COperation|mixed|null
   */
  function importOperation($element, $object, $import_tag) {
    /** @var COperation $_interv */
    $_interv = $this->getObjectFromElement($element, $object);
    $_ds     = $_interv->getDS();

    $where = array(
      "sejour_id"                  => $_ds->prepare("= ?", $_interv->sejour_id),
      "chir_id"                    => $_ds->prepare("= ?", $_interv->chir_id),
      "date"                       => $_ds->prepare("= ?", $_interv->date),
      "cote"                       => $_ds->prepare("= ?", $_interv->cote),
      "id_sante400.id_sante400_id" => "IS NULL",
    );
    $ljoin = array(
      "id_sante400" => "id_sante400.object_id = operations.operation_id AND
                            id_sante400.object_class = 'COperation' AND
                            id_sante400.tag = '$import_tag'",
    );

    $_matching = $_interv->loadList($where, null, null, null, $ljoin);

    if (count($_matching)) {
      $_interv = reset($_matching);
      CAppUI::stepAjax("%s '%s' retrouvée", UI_MSG_OK, CAppUI::tr($_interv->_class), $_interv->_view);
    }
    else {
      $is_new = !$_interv->_id;
      if ($msg = $_interv->store(false)) {
        $this->writeLog($msg, $element, UI_MSG_WARNING);

        return null;
      }

      CAppUI::stepAjax("%s '%s' " . ($is_new ? "créée" : "mise à jour"), UI_MSG_OK, CAppUI::tr($_interv->_class), $_interv->_view);
    }

    return $_interv;
  }

  /**
   * Import a CContentHTML from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CContentHTML|CMbObject|null
   */
  function importContentHTML($element, $object) {
    /** @var CContentHTML $_object */
    $_object          = $this->getObjectFromElement($element, $object);

      if (($this->options['date_min'] && $_object->last_modified < $this->options['date_min'])
          || ($this->options['date_max'] && $_object->last_modified > $this->options['date_max'])
      ) {
          return null;
      }

    $_object->content = stripslashes($_object->content);

    if (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * Import a CBanque from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CBanque|CMbObject|null
   */
  function importBanque($element, $object) {
    /** @var CBanque $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $_new_banque      = new CBanque();
    $_new_banque->nom = $_object->nom;
    $_new_banque->loadMatchingObjectEsc();

    if ($_new_banque->_id) {
      $_object = $_new_banque;

      CAppUI::stepAjax(CAppUI::tr($_object->_class) . " '%s' retrouvée", UI_MSG_OK, $_object);
    }
    else {
      if (!$this->storeObject($_object, $element)) {
        return null;
      }
    }

    return $_object;
  }

  /**
   * Import a CMedecin from a XML element
   *
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CMedecin|null
   */
  function importMedecin($element, $object) {
    /** @var CMedecin $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $siblings = $_object->loadExactSiblings();

    if ($siblings) {
      $_object = reset($siblings);
    }

    if (!$_object->_id) {
      $_object->actif = '0';
    }

    if (!$this->update_data && $_object->_id) {
      return $_object;
    }

    if (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element
   * @param CMbObject  $object  Object found
   *
   * @return CFactureEtablissement|null
   */
  function importFactureEtablissement($element, $object) {
    /** @var CFactureEtablissement $_object */
    $_object = $this->getObjectFromElement($element, $object);

    if (($this->options['date_min'] && $_object->ouverture < $this->options['date_min'])
      || ($this->options['date_max'] && $_object->ouverture > $this->options['date_max'])
    ) {
      return null;
    }

    $_object->loadMatchingObjectEsc();

    if (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CPrescription
   * @param CMbObject  $object  Prescription found
   *
   * @return CPrescription|null
   */
  function importPrescription($element, $object) {
    /** @var CPrescription $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $presc               = new CPrescription();
    $presc->object_class = $_object->object_class;
    $presc->object_id    = $_object->object_id;

    $presc->loadMatchingObjectEsc();

    if ($presc->_id) {
      $_object = $presc;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML Element for the line
   * @param CMbObject  $object  Line found
   *
   * @return CPrescriptionLineMedicament|null
   */
  function importPrescriptionLineMedicament($element, $object) {
    if (!CPrescription::isMPMActive()) {
        return null;
    }

    /** @var CPrescriptionLineMedicament $_object */
    $_object = $this->getObjectFromElement($element, $object);

    if (($this->options['date_min'] && $_object->debut && $_object->debut < $this->options['date_min'])
      || ($this->options['date_max'] && $_object->fin && $_object->fin > $this->options['date_max'])
    ) {
      return null;
    }

    $line                  = new CPrescriptionLineMedicament();
    $line->prescription_id = $_object->prescription_id;
    $line->code_cis        = $_object->code_cis;

    if ($_object->debut) {
      $line->debut = $_object->debut;
    }
    if ($_object->fin) {
      $line->fin = $_object->fin;
    }

    $line->loadMatchingObjectEsc();

    if ($line->_id) {
      $_object = $line;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CPrisePosologie
   * @param CMbObject  $object  CPrisePosologie found
   *
   * @return CPrisePosologie|null
   */
  function importPrisePosologie($element, $object) {
    /** @var CPrisePosologie $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $prise                     = new CPrisePosologie();
    $prise->object_class       = $_object->object_class;
    $prise->object_id          = $_object->object_id;
    $prise->quantite           = $_object->quantite;
    $prise->unite_prise        = $_object->unite_prise;
    $prise->moment_unitaire_id = $_object->moment_unitaire_id;

    $prise->loadMatchingObjectEsc();

    if ($prise->_id) {
      $_object = $prise;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CMomentUnitaire
   * @param CMbObject  $object  CMomentUnitaire found
   *
   * @return CMomentUnitaire|null
   */
  function importMomentUnitaire($element, $object) {
    /** @var CMomentUnitaire $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $moment          = new CMomentUnitaire();
    $moment->libelle = $_object->libelle;

    $moment->loadMatchingObjectEsc();

    if ($moment->_id) {
      $_object = $moment;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CPrescriptionLineElement
   * @param CMbObject  $object  CPrescriptionLineElement found
   *
   * @return CPrescriptionLineElement|null
   */
  function importPrescriptionLineElement($element, $object) {
    /** @var CPrescriptionLineElement $_object */
    $_object = $this->getObjectFromElement($element, $object);

    if (($this->options['date_min'] && $_object->debut && $_object->debut < $this->options['date_min'])
      || ($this->options['date_max'] && $_object->fin && $_object->fin > $this->options['date_max'])
    ) {
      return null;
    }

    $line                          = new CPrescriptionLineElement();
    $line->prescription_id         = $_object->prescription_id;
    $line->element_prescription_id = $_object->element_prescription_id;

    if ($_object->debut) {
      $line->debut = $_object->debut;
    }
    if ($_object->fin) {
      $line->fin = $_object->fin;
    }

    $line->loadMatchingObjectEsc();

    if ($line->_id) {
      $_object = $line;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CEelementPrescription
   * @param CMbObject  $object  Object found
   *
   * @return CElementPrescription|null
   */
  function importElementPrescription($element, $object) {
    /** @var CElementPrescription $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $element_presc                           = new CElementPrescription();
    $element_presc->libelle                  = $_object->libelle;
    $element_presc->category_prescription_id = $_object->category_prescription_id;

    $element_presc->loadMatchingObjectEsc();

    if ($element_presc->_id) {
      $_object = $element_presc;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CCategoryPrescription
   * @param CMbObject  $object  Object found
   *
   * @return CCategoryPrescription|null
   */
  function importCategoryPrescription($element, $object) {
    /** @var CCategoryPrescription $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $cat              = new CCategoryPrescription();
    $cat->nom         = $_object->nom;
    $cat->group_id    = $_object->group_id ?: null;
    $cat->function_id = $_object->function_id ?: null;
    $cat->user_id     = $_object->user_id ?: null;

    $cat->loadMatchingObjectEsc();

    if ($cat->_id) {
      $_object = $cat;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * @param DOMElement $element XML element for the CIdSante400
   * @param CMbObject  $object  Object found
   *
   * @return CIdSante400|null
   */
  function importExternalId($element, $object) {
    /** @var CIdSante400 $_object */
    $_object = $this->getObjectFromElement($element, $object);

    $ex_id = CIdSante400::getMatch($_object->object_class, $_object->tag, $_object->id400, $_object->object_id);

    if ($ex_id->_id) {
      $_object = $ex_id;
    }
    elseif (!$this->storeObject($_object, $element)) {
      return null;
    }

    return $_object;
  }

  /**
   * Count the valid directories for import int $directory
   *
   * @param string $directory Root dir to check directories
   *
   * @return int
   */
  public static function countValideDirs($directory) {
    $iterator         = new DirectoryIterator($directory);
    $count_valid_dirs = 0;

    foreach ($iterator as $_fileinfo) {
      if ($_fileinfo->isDot()) {
        continue;
      }

      if ($_fileinfo->isFile()) {
        continue;
      }

      if ($_fileinfo->isDir()) {
        if (strpos($_fileinfo->getFilename(), "CPatient-") === 0) {
          $count_valid_dirs++;
        }
      }
    }

    return $count_valid_dirs;
  }

  /**
   * @param string     $msg     Message to write
   * @param DOMElement $element DOM element tu get ID from
   * @param int        $type    Error type
   *
   * @return void
   */
  protected function writeLog($msg, $element = null, $type = UI_MSG_OK) {
    if (isset($this->options['log_file']) && $this->options['log_file'] != '') {
      $msg = ($element != null) ? $element->getAttribute('id') . ' : ' . $msg : $msg;

      $exists = file_exists($this->options['log_file']);
      if (!$exists) {
        $exists = touch($this->options['log_file']);
      }

      if ($exists) {
        $msg = $this->patient_name . ' : ' . $msg;
        file_put_contents($this->options['log_file'], $msg . "\n", FILE_APPEND);
      }
      else {
        try {
          CApp::log($msg);
        }
        catch (\Exception $e) {
          CAppUI::stepAjax($e->getMessage(), UI_MSG_WARNING);
        }
      }
    }
    else {
      CAppUI::stepAjax($msg, $type);
    }
  }

  /**
   * Getter fot nb_errors
   *
   * @return int
   */
  public function getErrorCount() {
    return $this->error_count;
  }

  /**
   * Return if a class is ignored or not
   *
   * @param string $class Classe name to check
   *
   * @return bool
   */
  protected function isIgnored($class) {
    return in_array($class, static::$_ignored_classes) || in_array($class, $this->_tmp_ignored_classes);
  }

  public function findSejour($patient_id, $date, $type, $user_id = null, $annule = '0', $group_id = null) {
    if (!$patient_id) {
      return null;
    }

    if (!$group_id) {
      $group_id = CGroups::loadCurrent()->_id;
    }

    // Recherche d'un séjour dont le debut est à la date passée en argument (sans le time)
    $date = CMbDT::date($date);

    $sejour = new CSejour;
    $ds = $sejour->getDS();
    $where  = array(
      "patient_id"   => $ds->prepare("= ?", $patient_id),
      "annule"       => $ds->prepare("= ?", $annule),
      "DATE(`sejour`.`entree`) " . $ds->prepare("= ?", $date),
    );

    if ($type) {
      $where['type'] = $ds->prepare("= ?", $type);
    }

    if ($user_id) {
      $where['praticien_id'] = $ds->prepare("= ?", $user_id);
    }

    if ($group_id) {
      $where['group_id'] = $ds->prepare("= ?", $group_id);
    }

    $sejour->loadObject($where);

    return $sejour;
  }
}
