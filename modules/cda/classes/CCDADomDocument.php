<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda;
use DOMElement;
use DOMNode;
use DOMNodeList;
use Exception;
use Ox\Core\CMbObject;
use Ox\Core\CMbXMLDocument;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\InteropResources\CInteropResources;

/**
 * Permet de cré
 */
class CCDADomDocument extends CMbXMLDocument {
  /** @var CExchangeCDA */
  public $_ref_exchange_cda;

  /** @var CInteropSender */
  public $_ref_sender;

  /**
   * @inheritdoc
   */
  function __construct($encoding = "UTF-8") {
    parent::__construct($encoding);

    $this->preserveWhiteSpace = true;
    $this->formatOutput       = false;
    $this->schemapath         = "modules/cda/resources";
    $this->schemafilename     = "$this->schemapath/CDA.xsd";
  }

  /**
   * @inheritdoc
   */
  function schemaValidate($filename = null, $returnErrors = false, $display_errors = true) {
    // Pas de validation car le module des ressources n'est pas installé
    $file = $filename ? $filename : $this->schemafilename;
    // Pas de validation car les schémas ne sont pas présents
    if (!CInteropResources::fileExists($file)) {
      trigger_error("Schemas are missing. Please add files in '$file' directory", E_USER_NOTICE);

      return true;
    }

    return parent::schemaValidate($file, $returnErrors, $display_errors);
  }

  /**
   * Ajoute du text en premier position
   *
   * @param DOMElement $nodeParent      DOMElement
   * @param String     $value           String
   * @param bool       $use_content_xml String
   *
   * @return void
   */
  function insertTextFirst($nodeParent, $value, $use_content_xml = false) {
    $value = utf8_encode($value);
    $firstNode = $nodeParent->firstChild;

    if ($use_content_xml && $value !== "") {
      $fragment = $this->createDocumentFragment();
      $fragment->appendXML($value);
      $nodeParent->insertBefore($fragment, $firstNode);
      return;
    }

    $nodeParent->insertBefore($this->createTextNode($value), $firstNode);
  }

  /**
   * Caste l'élement spécifié
   *
   * @param DOMNode $nodeParent DOMNode
   * @param String  $value      String
   *
   * @return void
   */
  function castElement($nodeParent, $value) {
    $value = utf8_encode($value);
    $attribute = $this->createAttributeNS("http://www.w3.org/2001/XMLSchema-instance", "xsi:type");
    $attribute->nodeValue = $value;
    $nodeParent->appendChild($attribute);
  }

  /**
   * @inheritdoc
   */
  function purgeEmptyElementsNode($node, $removeParent = true) {
    // childNodes undefined for non-element nodes (eg text nodes)
    if ($node->childNodes) {
      // Copy childNodes array
      $childNodes = array();
      foreach ($node->childNodes as $childNode) {
        $childNodes[] = $childNode;
      }

      // Browse with the copy (recursive call)
      foreach ($childNodes as $childNode) {
        $this->purgeEmptyElementsNode($childNode);
      }
    }
    // Remove if empty
    if (!$node->hasChildNodes() && !$node->hasAttributes() && $node->nodeValue === "") {
      $node->parentNode->removeChild($node);
    }
  }

  /**
   * Handle event
   *
   * @param CMbObject $object Object
   * @param array     $data   Datas
   */
  function handle(CMbObject $object, $data = array()) {
  }

  /**
   * Get content Nodes
   */
  function getContentNodes() {
    $data = array();

    $recordTarget = $this->queryNode("recordTarget", null, $varnull, true);
    $patientRole  = $this->queryNode("patientRole", $recordTarget, $data, true);
    $data["personIdentifiers"] = $this->getPatientIdentifiers($patientRole);

    return $data;
  }

  /**
   * Query
   *
   * @param string  $nodeName    The XPath to the node
   * @param DOMNode $contextNode The context node from which the XPath starts
   *
   * @return DOMNodeList
   */
  function query($nodeName, DOMNode $contextNode = null) {
    $xpath = new CCDAXPath($contextNode ? $contextNode->ownerDocument : $this);

    if ($contextNode) {
      return $xpath->query("cda:$nodeName", $contextNode);
    }

    return $xpath->query("cda:$nodeName");
  }

  /**
   * Get the node corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param array|null   $data        Nodes data
   * @param boolean      $root        Is root node ?
   *
   * @return DOMNode The node
   * @throws Exception
   */
  function queryNode($nodeName, DOMNode $contextNode = null, &$data = null, $root = false) {
    $xpath = new CCDAXPath($contextNode ? $contextNode->ownerDocument : $this);

    return $data[$nodeName] = $xpath->queryUniqueNode($root ? "//cda:$nodeName" : "cda:$nodeName", $contextNode);
  }

  /**
   * Get the nodeList corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param array|null   $data        Nodes data
   *
   * @return DOMNodeList
   */
  function queryNodes($nodeName, DOMNode $contextNode = null, &$data = null) {
    $nodeList = $this->query("$nodeName", $contextNode);
    foreach ($nodeList as $_node) {
      $data[$nodeName][] = $_node;
    }

    return $nodeList;
  }

  /**
   * Get the text of a node corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param string       $purgeChars  The chars to remove from the text
   * @param boolean      $addslashes  Escape slashes is the return string
   *
   * @return string
   */
  function queryTextNode($nodeName, DOMNode $contextNode, $purgeChars = "", $addslashes = false) {
    $xpath = new CCDAXPath($contextNode ? $contextNode->ownerDocument : $this);

    return $xpath->queryTextNode("cda:$nodeName", $contextNode, $purgeChars, $addslashes);
  }

  /**
   * Get the text of a attribute corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param string       $attName     Attribute name
   *
   * @return string
   */
  function queryAttributeNode($nodeName, DOMNode $contextNode, $attName) {
    $xpath = new CCDAXPath($contextNode ? $contextNode->ownerDocument : $this);

    return $xpath->queryAttributNode("cda:$nodeName", $contextNode, $attName);
  }

  /**
   * Get the value of attribute
   *
   * @param DOMNode $node       Node
   * @param string  $attName    Attribute name
   * @param string  $purgeChars The input string
   *
   * @return string
   */
  function getValueAttributNode(DOMNode $node, $attName, $purgeChars = "") {
    $xpath = new CCDAXPath($this);

    return $xpath->getValueAttributNode($node, $attName, $purgeChars);
  }

  /**
   * Get patient identifiers
   *
   * @return void
   */
  function getPatientIdentifiers(DOMNode $node) {
    $temp["PI"] = $this->queryAttributeNode(
      "id[@root='2.25.299518904337880959076241620201932965147.2.1']",
      $node,
      "extension"
    );

    return $temp;
  }

  /**
   * Get value
   *
   * @param DOMNode $node Node
   *
   * @return DOMNode
   * @throws Exception
   */
  function getValue(DOMNode $node) {
    return $this->queryNode("value", $node);
  }
}