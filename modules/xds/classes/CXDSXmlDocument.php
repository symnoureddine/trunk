<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Xds;

use DOMElement;
use DOMNode;
use Ox\Core\CMbString;
use Ox\Core\CMbXMLDocument;

/**
 * Document xml du XDS
 */
class CXDSXmlDocument extends CMbXMLDocument {
  public $patharchiveschema;
  public $dirschemaname;
  public $schemafilename;

  /**
   * @see parent::__construct()
   */
  function __construct() {
    parent::__construct("UTF-8");
    $this->preserveWhiteSpace = true;
    $this->formatOutput = false;
  }


  /**
   * @inheritdoc
   */
  function schemaValidate($filename = null, $returnErrors = false, $display_errors = true) {
    $this->patharchiveschema = "modules/xds/resources/";
    $this->schemafilename    = "$this->patharchiveschema/XDS.b_DocumentRepository.xsd";

    return parent::schemaValidate($this->schemafilename, $returnErrors, $display_errors);
  }

  /**
   * Création d'élément RIM
   *
   * @param String  $name        Nom du noeud
   * @param String  $value       Valeur du noeud
   * @param DOMNode $contextNode Noeud de référence
   *
   * @return DOMElement
   */
  function createRimRoot($name, $value = null, DOMNode $contextNode = null) {
    $elParent = $contextNode ? $contextNode : $this;

    return parent::addElement($elParent, "rim:$name", $value, "urn:oasis:names:tc:ebxml-regrep:xsd:rim:3.0");
  }

  /**
   * Création d'élément racine lcm
   *
   * @param String $name  Nom du noeud
   * @param String $value Valeur du noeud
   *
   * @return DOMElement
   */
  function createLcmRoot($name, $value = null) {
    return $this->createLcmElement($this, $name, $value);
  }

  /**
   * Création d'un noeud pour l'entrepôt
   *
   * @param DOMNode $nodeParent Noeud parent
   * @param String  $name       Nom du noeud
   * @param String  $value      Valeur du noeud
   *
   * @return DOMElement
   */
  function createDocumentRepositoryElement($nodeParent, $name, $value = null) {
    return parent::addElement($nodeParent, "xds:$name", $value, "urn:ihe:iti:xds-b:2007");
  }

  /**
   * Création d'un noeud pour query
   *
   * @param DOMNode $nodeParent Noeud parent
   * @param String  $name       Nom du noeud
   * @param String  $value      Valeur du noeud
   *
   * @return DOMElement
   */
  function createQueryElement($nodeParent, $name, $value = null) {
    return parent::addElement($nodeParent, "query:$name", $value, "urn:oasis:names:tc:ebxml-regrep:xsd:query:3.0");
  }

  /**
   * Création d'un noeud pour lcm
   *
   * @param DOMNode $nodeParent Noeud parent
   * @param String  $name       Nom du noeud
   * @param String  $value      Valeur du noeud
   *
   * @return DOMElement
   */
  function createLcmElement($nodeParent, $name, $value = null) {
    return parent::addElement($nodeParent, "lcm:$name", $value, "urn:oasis:names:tc:ebxml-regrep:xsd:lcm:3.0");
  }

  /**
   * Création de la racine Slot
   *
   * @param String $name Nom du noeud
   *
   * @return void
   */
  function createSlotRoot($name) {
    $element = $this->createRimRoot("Slot");
    $this->addAttribute($element, "name", $name);
  }

  /**
   * Création de la racine RegistryPackageRoot
   *
   * @param String $id Identifiant
   *
   * @return void
   */
  function createRegistryPackageRoot($id) {
    $element = $this->createRimRoot("RegistryPackage");
    $this->addAttribute($element, "id", $id);
  }

  /**
   * Création de valeurs Slot
   *
   * @param String[] $data Données du slot
   *
   * @return void
   */
  function createSlotValue($data) {
    $valueList = $this->createRimRoot("ValueList", null, $this->documentElement);
    foreach ($data as $_data) {
      $this->createRimRoot("Value", CMbString::htmlspecialchars($_data), $valueList);
    }
  }

  /**
   * Création de la racine pour le Name et Description
   *
   * @param String $name Nom du noeud
   *
   * @return void
   */
  function createNameDescriptionRoot($name) {
    $this->createRimRoot($name);
  }

  /**
   * Création du Localized
   *
   * @param String $value   Valeur
   * @param String $charset Charset
   * @param String $lang    Langue
   *
   * @return void
   */
  function createLocalized($value, $charset, $lang) {
    $element = $this->createRimRoot("LocalizedString");
    $this->addAttribute($element, "value"  , $value);
    $this->addAttribute($element, "charset", $charset);
   // $this->addAttribute($element, "lang"   , $lang);
  }

  /**
   * Création du Version Info
   *
   * @param String $value Valeur
   *
   * @return void
   */
  function createVersionInfo($value) {
    $element = $this->createRimRoot("VersionInfo");
    $this->addAttribute($element, "VersionName", $value);
  }

  /**
   * Création de la racine de classification
   *
   * @param String $id                 Identifiant
   * @param String $classification     ClassificationScheme
   * @param String $classified         ClassifiedObject
   * @param String $nodeRepresentation Noderepresentation
   *
   * @return void
   */
  function createClassificationRoot($id, $classification, $classified, $nodeRepresentation) {
    $element = $this->createRimRoot("Classification");
    $this->addAttribute($element, "id"                  , $id);
    $this->addAttribute($element, "classificationScheme", $classification);
    $this->addAttribute($element, "classifiedObject"    , $classified);
    $this->addAttribute($element, "nodeRepresentation"  , $nodeRepresentation);
  }

  /**
   * Création de la racine ExternalIdentifier
   *
   * @param String $id             Identifiant
   * @param String $identification Identificationscheme
   * @param String $registry       registryObject
   * @param String $value          Valeur
   *
   * @return void
   */
  function createExternalIdentifierRoot($id, $identification, $registry, $value) {
    $element = $this->createRimRoot("ExternalIdentifier");
    $this->addAttribute($element, "id"                  , $id);
    $this->addAttribute($element, "identificationScheme", $identification);
    $this->addAttribute($element, "registryObject"      , $registry);
    $this->addAttribute($element, "value"               , $value);
  }

  /**
   * Création de la racine ExtrinsicObject
   *
   * @param String $id         Identifiant
   * @param String $mimeType   MimeType
   * @param String $objectType ObjectType
   * @param String $status     Status
   * @param String $lid        Lid
   *
   * @return void
   */
  function createExtrinsicObjectRoot($id, $mimeType, $objectType, $status = null, $lid = null) {
    $element = $this->createRimRoot("ExtrinsicObject");
    $this->addAttribute($element, "id"        , $id);
    $this->addAttribute($element, "mimeType"  , $mimeType);
    $this->addAttribute($element, "objectType", $objectType);
    if ($status) {
      $this->addAttribute($element, "status", $status);
    }
    if ($lid) {
      $this->addAttribute($element, "lid", $lid);
    }
  }

  /**
   * Création de la racine Submission
   *
   * @param String $id                 Identifiant
   * @param String $classificationNode ClassificationNode
   * @param String $classifiedObject   ClassifiedObject
   *
   * @return void
   */
  function createSubmissionRoot($id, $classificationNode, $classifiedObject) {
    $element = $this->createRimRoot("Classification");
    $this->addAttribute($element, "id"                , $id);
    $this->addAttribute($element, "classificationNode", $classificationNode);
    $this->addAttribute($element, "classifiedObject"  , $classifiedObject);
  }

  /**
   * Création de la racine association
   *
   * @param String $id           Identifiant
   * @param String $type         associationType
   * @param String $sourceObject SourceObject
   * @param String $targetObject TargetObject
   * @param String $objectType   ObjectType
   *
   * @return DOMElement
   */
  function createAssociationRoot($id, $type, $sourceObject, $targetObject, $objectType) {
    $element = $this->createRimRoot("Association");
    $this->addAttribute($element, "id"             , $id);
    $this->addAttribute($element, "associationType", $type);
    $this->addAttribute($element, "objectType"     , $objectType);
    $this->addAttribute($element, "sourceObject"   , $sourceObject);
    $this->addAttribute($element, "targetObject"   , $targetObject);

    return $element;
  }

  /**
   * Création de la racine ObjectList
   *
   * @return void
   */
  function createRegistryObjectListRoot() {
    $this->createRimRoot("RegistryObjectList");
  }

  /**
   * Création de la racine XDS
   *
   * @return void
   */
  function createSubmitObjectsRequestRoot() {
    $element = $this->createLcmRoot("SubmitObjectsRequest");
    $element->appendChild($this->documentElement);
    $this->appendChild($element);
  }
}