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
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CMbPath;
use Ox\Core\CMbXMLDocument;
use Ox\Core\CMbXPath;
use Ox\Core\Module\CModule;
use Ox\Core\CValue;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Hl7\Events\XDM\CHL7v3EventXDMException;
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
use Ox\Mediboard\System\CSourceFileSystem;

/**
 * Class CHL7v3RecordDistributeDocumentSetOnMedia
 * Record distribute document set on media
 */
class CHL7v3RecordDistributeDocumentSetOnMedia implements IShortNameAutoloadable {
  const TMP_DIR    = "tmp/ihe_xdm";
  const EVENT_NAME = "CHL7v3EventXDMDistributeDocumentSetOnMedia";

  public $codes = array();
  public $dom_medatada = null;
  public $dom_cda      = null;
  public $xpath        = null;

  /**
   * Extract the given attachment and return it's path
   *
   * @param array $file Upload file
   *
   * @return false|string False or the path of the extracted content
   */
  protected static function getArchiveFilePath($file) {
    /* Creates a temporary dir */
    $dir = self::TMP_DIR;

    CMbPath::forceDir($dir);
    $path = "{$dir}/".CMbArray::get($file, "name");
    $archive = "{$path}.zip";

    /* Must copy the file because the CMbPath::extract function uses the file extension to detect the type of compression */
    if (!copy($file["tmp_name"], $archive) || !CMbPath::extract($archive, $path)) {
      $path = false;
    }

    return $path;
  }

  /**
   * Remove the extracted files and the archive
   *
   * @param string $path The file path
   *
   * @return void
   */
  protected static function cleanFiles($path) {
    CMbPath::remove($path);
    CMbPath::remove("{$path}.zip");
  }

  /**
   * Handle event
   *
   * @param CMbObject $object Object
   * @param array     $data   Nodes data
   *
   * @return null|string
   * @throws Exception
   */
  public function handle(CMbObject $object, $data) {
    $result = false;
    $file = CMbArray::get($data, "file");

    $senders = CMbArray::get(CInteropSender::getObjectsBySupportedEvents(array(self::EVENT_NAME)), self::EVENT_NAME);
    /** @todo On récupère le premier */
    $sender = reset($senders);

    // 0 - Creating the trace
    $file_traceability = new CFileTraceability();
    $file_traceability->received_datetime = "now";
    $file_traceability->user_id           = CMediusers::get()->_id;
    $file_traceability->actor_class       = $sender->_class;
    $file_traceability->actor_id          = $sender->_id;
    $file_traceability->group_id          = $sender->group_id;
    $file_traceability->source_name       = "ihe-xdm";
    $file_traceability->status            = "pending";

    /* Extract the archive and return the path */
    if (!$path = self::getArchiveFilePath($file)) {
      return null;
    }

    // @todo foreach sur les subsets
    $subset = "IHE_XDM/SUBSET01";
    if (!$files = CMbPath::getFiles("$path/$subset")) {dump($files);
      return $this->setError($file_traceability, $object, $path, CHL7v3EventXDMException::EMPTY_ARCHIVE_ZIP);
    }

    dump("1 - Extract metadata");
    // 1 - Extract metadata
    $matches = preg_grep('/metadata.xml/i', $files);
    if (!$matches) {
      return $this->setError($file_traceability, $object, $path, CHL7v3EventXDMException::METADATA_MISSING);
    }

    dump("2 - Parse metadata");
    // 2 - Parse metadata : DocumentEntry, hash et size
    $file_medata_path = reset($matches);
    $metadata = $this->handleMetadata($file_medata_path);

    foreach ($metadata as $_cda_name => $_metatada) {
      $cda_file = "$path/$subset/$_cda_name";
      dump("3 - Comparison of metadata with CDA metadata");
      // 3 - Comparison of metadata with CDA metadata : hash + sizeCFile

      // CDA document not found
      if (!"$path/$subset/$_cda_name") {
        return $this->setError($file_traceability, $object, $cda_file, CHL7v3EventXDMException::CDA_MISSING);
      }

      // Not CDA File
      if (!$this->isFileCDADocument($cda_file)) {
        return $this->setError($file_traceability, $object, $cda_file, CHL7v3EventXDMException::IS_NO_CDA_FILE);
      }

      // Comparison metadata
      $cda_content = file_get_contents($cda_file);

      $cda_size = strlen($cda_content);
      if ((CMbArray::get($_metatada, "size") != $cda_size)) {
        return $this->setError($file_traceability, $object, $cda_file, CHL7v3EventXDMException::METADATA_DIFFERENT_SIZE);
      }

      $cda_hash = strtoupper(sha1($cda_content));
      if (strtoupper(CMbArray::get($_metatada, "hash")) != $cda_hash) {
        return $this->setError($file_traceability, $object, $cda_file, CHL7v3EventXDMException::METADATA_DIFFERENT_HASH);
      }

      dump("4 - Settlement of the trace");
      // 4 - Settlement of the trace
      $return = $this->handleCDA($cda_file, $_metatada, $file_traceability);
      if (is_numeric($return)) {
        return $this->setError($file_traceability, $file_traceability, $cda_file, $return);
      }

      if ($return instanceof CFile) {
        $cfile = $return;
        dump("5 - Combine style sheets");
        // 5 - Combine style sheets

        $stylesheets = preg_grep('/xsl/i', $files);
        foreach ($stylesheets as $_stylesheet) {
          $cstylesheet = new CFile();
          $cstylesheet->setObject($cfile);
          $cstylesheet->file_name = CMbPath::getFileName($_stylesheet);
          $cstylesheet->file_type = CMbPath::guessMimeType($_stylesheet);
          $cstylesheet->annule    = 0;
          $cstylesheet->loadMatchingObject();
          $cstylesheet->file_date = "now";
          $content = file_get_contents($_stylesheet);
          $cstylesheet->doc_size  = strlen($content);
          $cstylesheet->fillFields();
          $cstylesheet->updateFormFields();
          $cstylesheet->setContent($content);
          $cstylesheet->store();
        }
      }
    }

    self::cleanFiles($path);

    return $result;
  }

  /**
   * @param CFileTraceability $file_traceability
   * @param CMbObject         $object
   * @param                   $filepath
   * @param                   $exception_code
   *
   * @return bool
   * @throws Exception
   */
  private function setError(CFileTraceability $file_traceability, CMbObject $object, $filepath, $exception_code) {
    $exception = new CHL7v3EventXDMException($exception_code);
    $file_traceability->msg_error = $exception->getMessage();

    // Dans le cas d'une archive ZIP invalide, on prend le "ZIP archive"
    if ($exception_code == CHL7v3EventXDMException::EMPTY_ARCHIVE_ZIP || $exception_code == CHL7v3EventXDMException::METADATA_MISSING
    ) {
      $filepath.= ".zip";
    }

    // Création du CFile
    $file = new CFile();
    if ($object && $object->_id) {
      $file->setObject($object);
    }
    $file->file_name = CMbPath::getFileName($filepath);
    $file->file_type = CMbPath::guessMimeType($filepath);
    $file->annule    = 0;
    $file->loadMatchingObject();

    $file->file_date = "now";
    $content = file_get_contents($filepath);
    $file->doc_size  = strlen($content);

    $file->fillFields();
    $file->updateFormFields();

    if (!$content) {
      $exception = new CHL7v3EventXDMException(CHL7v3EventXDMException::EMPTY_FILE);
      $file_traceability->msg_error = $exception->getMessage();
      $file_traceability->store();

      return false;
    }

    // Dans le cas où l'on n'a aucune cible pour le fichier de traçabilité on va le créer pour le faire pointer sur lui-même
    if ((!$object || !$object->_id) && $file_traceability) {
      $file_traceability->store();
      $file->setObject($file_traceability);
    }

    if (!$file->object_class || !$file->object_id) {
      return false;
    }

    $file->setContent($content);

    if ($msg = $file->store()) {
      $file_traceability->delete();
      return false;
    }

    if ($file_traceability && $file_traceability->_id) {
      $file_traceability->setObject($file);
      $file_traceability->store();
    }

    return false;
  }

  /**
   * Handle metadata
   *
   * @param string $file_medata_path File
   *
   * @return array
   * @throws Exception
   */
  public function handleMetadata($file_medata_path) {
    $dom_metadata = new CMbXMLDocument('utf-8');
    $dom_metadata->load($file_medata_path);

    return $this->getMetadataNodes($dom_metadata);
  }

  /**
   * Get data nodes
   *
   * @param CMbXMLDocument $dom DOM metadatas
   *
   * @return array Get nodes
   * @throws Exception
   *
   */
  function getMetadataNodes(CMbXMLDocument $dom) {
    $this->xpath = $xpath = new CMbXPath($dom);
    $xpath->registerNamespace("rs", "urn:oasis:names:tc:ebxml-regrep:xsd:rs:3.0");
    $xpath->registerNamespace("rim", "urn:oasis:names:tc:ebxml-regrep:xsd:rim:3.0");

    $extrinsicObjects = $xpath->query("//rim:ExtrinsicObject");
    $metadata = array();
    if (!$extrinsicObjects) {
      return $metadata;
    }

    foreach ($extrinsicObjects as $_extrinsicObject) {
      // URI
      $metadata[$URI]["uri"] = $URI = $xpath->queryTextNode("rim:Slot[@name='URI']", $_extrinsicObject);

      // Hash
      $metadata[$URI]["hash"]= $xpath->queryTextNode("rim:Slot[@name='hash']", $_extrinsicObject);

      // Creation Time
      $metadata[$URI]["creationTime"] = $xpath->queryTextNode("rim:Slot[@name='creationTime']", $_extrinsicObject);

      // Size
      $metadata[$URI]["size"] = $xpath->queryTextNode("rim:Slot[@name='size']", $_extrinsicObject);

      // RepositoryUniqueId
      $metadata[$URI]["repositoryUniqueId"] = $xpath->queryTextNode("rim:Slot[@name='repositoryUniqueId']", $_extrinsicObject);

      // EntryUniqueId
      if ($node_entryUniqueId = $xpath->getNode("rim:ExternalIdentifier[@identificationScheme='urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab']", $_extrinsicObject)){
        $metadata[$URI]["entryUniqueId"] = $xpath->getValueAttributNode($node_entryUniqueId, "value");
      }

      // Version
      if ($node_version = $xpath->getNode("rim:VersionInfo", $_extrinsicObject)) {
        $metadata[$URI]["version"] = $xpath->getValueAttributNode($node_version, "versionName");
      }

      // extrinsicNode
      $metadata[$URI]["extrinsicNode"] = $_extrinsicObject;
    }

    return $metadata;
  }

  /**
   * Check if the given file is an exam report in XDM format, and if it is, handle it
   *
   * @param string $file The file path
   *
   * @return bool
   * @throws Exception
   */
  protected static function isFileCDADocument($file) {
    $is_cda_document = false;
    $content = self::encode(file_get_contents($file));
    $xml = new CMbXMLDocument('ISO-8859-1');
    $xml->loadXML($content);
    $xpath = new CMbXPath($xml);
    $xpath->registerNamespace("cda", "urn:hl7-org:v3");

    $node = $xpath->queryUniqueNode('//cda:ClinicalDocument/cda:code');
    /* Check if the file is a cda document */
    if ($node && strtolower($node->getAttribute('code')) == 'synth') {
      $is_cda_document = true;
    }

    return $is_cda_document;
  }


  /**
   * Detect the encoding of the content, and return an UTF-8 string
   *
   * @param string $content The XML string
   *
   * @return string
   */
  protected static function encode($content) {
    if (strpos($content, 'UTF-8') !== false || strpos($content, 'utf-8') !== false) {
      $content = str_replace(array('UTF-8', 'utf-8'), 'ISO-8859-1', $content);
    }

    if (strpos($content, '<?xml') === false) {
      $content = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n{$content}";
    }
    else {
      $content = substr($content, strpos($content, '<?xml'));
    }

    return $content;
  }

  /**
   * Handle metadata
   *
   * @param string            $file_cda_path     File
   * @param array             $metatada          Metadata
   * @param CFileTraceability $file_traceability File traceability
   *
   * @return int|CFile File success, int error
   * @throws Exception
   */
  public function handleCDA($file_cda_path, $metatada, CFileTraceability $file_traceability) {
    $dom_cda = new CMbXMLDocument('utf-8');
    $dom_cda->load($file_cda_path);

    $nodes   = $this->getCdANodes($dom_cda);
    $patient = CMbArray::get($nodes, "patient");

    $clone_traceability = clone($file_traceability);
    $clone_traceability->patient_name          = $patient->nom;
    $clone_traceability->patient_birthname     = $patient->nom_jeune_fille;
    $clone_traceability->patient_firstname     = $patient->prenom;
    $clone_traceability->patient_date_of_birth = $patient->naissance;

    $cfile = new CFile();
    $cfile->author_id = CAppUI::$user->_id;
    $cfile->file_date = CMbDT::dateTime();
    $cfile->file_name = CMbArray::get($metatada, "uri");

    // Dans le cas où l'on n'a aucune cible pour le fichier de traçabilité on va le créer pour le faire pointer sur lui-même
    $clone_traceability->store();
    $cfile->setObject($clone_traceability);

    $cfile->fillFields();

    $content = file_get_contents($file_cda_path);
    $cfile->setContent($content);

    $cfile->file_type = 'application/xml';
    $cfile->type_doc_dmp = '1.2.250.1.213.1.1.4.12^SYNTH';
    if ($msg = $cfile->store()) {
      return CHL7v3EventXDMException::ERROR_STORE_FILE;
    }

    $clone_traceability->setObject($cfile);

    if ($msg = $clone_traceability->store()) {
      return CHL7v3EventXDMException::ERROR_STORE_TRACEABILITY;
    }

    return $cfile;
  }

  /**
   * Get data nodes
   *
   * @param CMbXMLDocument $dom DOM CDA
   *
   * @return array Get nodes
   * @throws Exception
   *
   */
  function getCdANodes(CMbXMLDocument $dom) {
    $this->xpath = $xpath = new CMbXPath($dom);
    $xpath->registerNamespace("cda", "urn:hl7-org:v3");

    $nodes = array();

    // Retrieving patient information from the message
    $node = $xpath->queryUniqueNode('//cda:recordTarget/cda:patientRole');
    if (!$node) {
      return $nodes;
    }

    $first_name   = $xpath->queryTextNode('./cda:patient/cda:name/cda:given', $node);
    $birth_name   = $xpath->queryTextNode('./cda:patient/cda:name/cda:family[@qualifier="BR"]', $node);
    $marital_name = $xpath->queryTextNode('./cda:patient/cda:name/cda:family[@qualifier="SP"]', $node);
    $dob_node = $xpath->queryUniqueNode('./cda:patient/cda:birthTime', $node);
    $dob = null;
    if ($dob_node) {
      $dob = $dob_node->getAttribute('value');
      if ($dob && strlen($dob) >= 8) {
        $dob = substr($dob, 0, 4) . '-' . substr($dob, 4, 2) . '-' . substr($dob, 6, 2);
      }
    }

    $gender = null;
    $gender_node = $xpath->queryUniqueNode('./cda:patient/cda:administrativeGenderCode', $node);
    if ($gender_node) {
      $gender = utf8_decode(strtolower($gender_node->getAttribute('code')));
    }

    $patient = new CPatient();
    if ($marital_name && $birth_name && $marital_name != $birth_name) {
      $patient->nom = $marital_name;
      $patient->nom_jeune_fille  = $birth_name;
    }
    elseif ($birth_name) {
      $patient->nom = $birth_name;
    }
    elseif ($marital_name) {
      $patient->nom = $marital_name;
    }

    $patient->prenom    = $first_name;
    $patient->naissance = $dob;
    $patient->sexe      = $gender;

    $nodes["patient"] = $patient;

    return $nodes;
  }
}
