<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use DOMNode;
use DOMNodeList;
use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbFieldSpec;
use Ox\Core\CMbObject;
use Ox\Core\CMbSecurity;
use Ox\Core\CMbXMLDocument;
use Ox\Core\Module\CModule;
use Ox\Core\CValue;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Eai\CInteropActor;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Hl7\Events\CHL7Event;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Doctolib\CSenderHL7v2Doctolib;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CConstantesMedicales;
use Ox\Mediboard\Patients\CMedecin;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Class CHL7v2MessageXML 
 * Message XML HL7
 */
class CHL7v2MessageXML extends CMbXMLDocument {

  /** @var CExchangeHL7v2 */
  public $_ref_exchange_hl7v2;

  /** @var CInteropSender */
  public $_ref_sender;

  /** @var CInteropReceiver */
  public $_ref_receiver;

  /** @var string */
  public $_is_i18n;

  /**
   * Get event
   *
   * @param string $event_name Event name
   * @param string $encoding   Encoding
   *
   * @return CHL7v2MessageXML
   */
  static function getEventType($event_name = null, $encoding = "utf-8") {
    if (!$event_name) {
      return new CHL7v2MessageXML($encoding);
    }

    // Intègre l'ACK dans l'échange créé
    if ($event_name === "CHL7v2EventACK") {
      return new CHL7v2LinkACK($encoding);
    }

    list($event_type, $event_code) = str_split($event_name, strlen("CHL7vXEventXXX"));
    $event_code = substr($event_code, 0, 3);

    if ($event_type === "CHL7v2EventADT") {
      // Création d'un nouveau patient - Mise à jour d'information du patient
      if (CMbArray::in($event_code, CHL7v2RecordPerson::$event_codes)) {
        return new CHL7v2RecordPerson($encoding);
      }
      
      // Fusion de deux patients
      if (CMbArray::in($event_code, CHL7v2MergePersons::$event_codes)) {
        return new CHL7v2MergePersons($encoding);
      }
      
      // Changement de la liste d'identifiants du patient
      if (CMbArray::in($event_code, CHL7v2ChangePatientIdentifierList::$event_codes)) {
        return new CHL7v2ChangePatientIdentifierList($encoding);
      }  
      
      // Création d'une venue - Mise à jour d'information de la venue
      if (CMbArray::in($event_code, CHL7v2RecordAdmit::$event_codes)) {
        return new CHL7v2RecordAdmit($encoding);
      }

      // Changement du patient par un autre
      if (CMbArray::in($event_code, CHL7v2MoveAccountInformation::$event_codes)) {
        return new CHL7v2MoveAccountInformation($encoding);
      }

      // Association / Déssociation
      if (CMbArray::in($event_code, CHL7v2LinkUnlink::$event_codes)) {
        return new CHL7v2LinkUnlink($encoding);
      }

      // Re-numérotation numéro de pré-admission
      if (CMbArray::in($event_code, CHL7v2ChangePatientAccountNumber::$event_codes)) {
        return new CHL7v2ChangePatientAccountNumber($encoding);
      }
    }    
    
    // Création des résultats d'observations  
    if ($event_type === "CHL7v2EventORU") {
      return new CHL7v2RecordObservationResultSet($encoding);  
    }
    
    // Création des consultations
    if ($event_type === "CHL7v2EventSIU") {
      return new CHL7v2RecordAppointment($encoding);  
    }

    // Récupération des résultats du PDQ
    if ($event_type === "CHL7v2EventQBP") {
      // Analyse d'une réponse reçu après une requête
      if (CMbArray::in($event_code, CHL7v2ReceivePatientDemographicsResponse::$event_codes)) {
        return new CHL7v2ReceivePatientDemographicsResponse($encoding);
      }

      // Produire une réponse sur une requête
      if (CMbArray::in($event_code, CHL7v2GeneratePatientDemographicsResponse::$event_codes)) {
        return new CHL7v2GeneratePatientDemographicsResponse($encoding);
      }
    }

    // Récupération des résultats du QCN
    if ($event_type === "CHL7v2EventQCN") {
      // Suppression d'une requête
      if (CMbArray::in($event_code, CHL7v2CancelPatientDemographicsQuery::$event_codes)) {
        return new CHL7v2CancelPatientDemographicsQuery($encoding);
      }
    }

    if ($event_type === "CHL7v2EventORM") {
      // Analyse d'une réponse reçu après une requête
      if (CMbArray::in($event_code, CHL7v2ReceiveOrderMessage::$event_codes)) {
        return new CHL7v2ReceiveOrderMessage($encoding);
      }
    }

    if ($event_type === "CHL7v2EventMFN") {
      // Master files notification
      if (CMbArray::in($event_code, CHL7v2ReceiveMasterFilesNotification::$event_codes)) {
        return new CHL7v2ReceiveMasterFilesNotification($encoding);
      }
    }
    
    return new CHL7v2MessageXML($encoding);
  }

  /**
   * Construct
   *
   * @param string $encoding Encoding
   */
  function __construct($encoding = "utf-8") {
    parent::__construct($encoding);

    $this->formatOutput = true;
  }

  /**
   * Add namespaces
   *
   * @param string $name Schema
   *
   * @return void
   */
  function addNameSpaces($name) {
    // Ajout des namespace pour XML Spy
    $this->addAttribute($this->documentElement, "xmlns", "urn:hl7-org:v2xml");
    $this->addAttribute($this->documentElement, "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
    $this->addAttribute($this->documentElement, "xsi:schemaLocation", "urn:hl7-org:v2xml $name.xsd");
  }

  /**
   * Add element
   *
   * @param DOMNode $elParent Parent element
   * @param string  $elName   Name
   * @param string  $elValue  Value
   * @param string  $elNS     Namespace
   *
   * @return mixed
   */
  function addElement(DOMNode $elParent, $elName, $elValue = null, $elNS = "urn:hl7-org:v2xml") {
    return parent::addElement($elParent, $elName, $elValue, $elNS);
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
    $xpath = new CHL7v2MessageXPath($contextNode ? $contextNode->ownerDocument : $this);   
    
    if ($contextNode) {
      return $xpath->query($nodeName, $contextNode);
    }
    
    return $xpath->query($nodeName);
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
    $xpath = new CHL7v2MessageXPath($contextNode ? $contextNode->ownerDocument : $this);   
        
    return $data[$nodeName] = $xpath->queryUniqueNode($root ? "//$nodeName" : "$nodeName", $contextNode);
  }

  /**
   * Get the nodeList corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param array|null   $data        Nodes data
   * @param boolean      $root        Is root node ?
   *
   * @return DOMNodeList
   */
  function queryNodes($nodeName, DOMNode $contextNode = null, &$data = null, $root = false) {
    $nodeList = $this->query("$nodeName", $contextNode);
    foreach ($nodeList as $_node) {
      $data[$nodeName][] = $_node;
    }
    
    return $nodeList;
  }

  /**
   * Get the node corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param array|null   $data        Nodes data
   * @param int          $index       Index
   *
   * @return DOMNode The node
   */
  function queryNodeByIndex($nodeName, DOMNode $contextNode = null, &$data = null, $index = 0) {
    $xpath = new CHL7v2MessageXPath($contextNode ? $contextNode->ownerDocument : $this);

    return $data[$nodeName] = $xpath->getNode("$nodeName", $contextNode, $index);
  }

  /**
   * Get the text of a node corresponding to an XPath
   *
   * @param string       $nodeName    The XPath to the node
   * @param DOMNode|null $contextNode The context node from which the XPath starts
   * @param boolean      $root        Is root node ?
   *
   * @return string
   * @throws Exception
   */
  function queryTextNode($nodeName, DOMNode $contextNode = null, $root = false) {
    $xpath = new CHL7v2MessageXPath($contextNode ? $contextNode->ownerDocument : $this);   
    
    return $xpath->queryTextNode($nodeName, $contextNode);
  }

  /**
   * Get segment
   *
   * @param string    $name   Segment name
   * @param array     $data   Data
   * @param CMbObject $object Object
   *
   * @return void
   */
  function getSegment($name, $data, $object) {
    if (!array_key_exists($name, $data) || $data[$name] === null) {
      return;
    }
    
    $function = "get$name";
    
    $this->$function($data[$name], $object, $data);
  }

  /**
   * Get fields in MSH segment
   *
   * @return array
   * @throws Exception
   */
  function getMSHEvenementXML() {
    $data = array();
    
    $MSH = $this->queryNode("MSH", null, $foo, true);
    
    $data['dateHeureProduction']   = CMbDT::dateTime($this->queryTextNode("MSH.7/TS.1", $MSH));
    $data['identifiantMessage']    = $this->queryTextNode("MSH.10", $MSH);

    $data['receiving_application'] = $this->queryTextNode("MSH.3/HD.1", $MSH);
    $data['receiving_facility']    = $this->queryTextNode("MSH.4/HD.1", $MSH);
    
    return $data;
  }

  /**
   * Get PI identifier
   *
   * @param DOMNode       $node   Node
   * @param array         $data   Data
   * @param CInteropActor $sender Sender
   *
   * @return void
   * @throws Exception
   */
  function getPIIdentifier(DOMNode $node, &$data, CInteropActor $sender) {
    if (CMbArray::get($data, "PI")) {
      return;
    }

    $id_number            = $this->queryTextNode("CX.1", $node);
    $namespace_id         = $this->queryTextNode("CX.4/HD.1", $node);
    $universal_id         = $this->queryTextNode("CX.4/HD.2", $node);
    $identifier_type_code = $this->queryTextNode("CX.5", $node);

    if (CHL7v2Message::$handle_mode === "simple") {
      $data["PI"] = $id_number;
      return;
    }

    $control_identifier_type_code = CValue::read($sender->_configs, "control_identifier_type_code");
    $search_master_IPP = CValue::read($sender->_configs, "search_master_IPP");
    if ($search_master_IPP) {
      $domain = CDomain::getMasterDomain("CPatient", $sender->group_id);
      if ($domain->namespace_id != $namespace_id) {
        return;
      }

      if ($control_identifier_type_code) {
        if ($identifier_type_code === "PI") {
          $data["PI"] = $id_number;
        }
      }
      else {
        $data["PI"] = $id_number;
      }

      return;
    }

    if ($identifier_type_code === "PI") {
      $data["PI"] = $id_number;
    }
  }

  /**
   * Get PI identifier
   *
   * @param DOMNode       $node   Node
   * @param array         $data   Data
   * @param CInteropActor $sender Sender
   *
   * @return void
   * @throws Exception
   */
  function getOtherPIIdentifiers(DOMNode $node, &$data, CInteropActor $sender) {
    $identifier_type_code = $this->queryTextNode("CX.5", $node);
    if ($identifier_type_code != "PI") {
      return;
    }

    if (!CValue::read($sender->_configs, "retrieve_all_PI_identifiers")) {
      return;
    }

    $id_number     = $this->queryTextNode("CX.1"     , $node);
    $namespace_id  = $this->queryTextNode("CX.4/HD.1", $node);
    $universal_id  = $this->queryTextNode("CX.4/HD.2", $node);
    if (!$namespace_id && !$universal_id) {
      return;
    }

    $domains       = CDomain::getAllDomains("CPatient", false);
    $master_domain = CDomain::getMasterDomain("CPatient", $sender->group_id);
    unset($domains[$master_domain->_id]);
    foreach ($domains as $_domain) {
      // On ne connaît pas le domaine
      if (($namespace_id && $_domain->namespace_id != $namespace_id)) {
        continue;
      }

      // On ne connaît pas le domaine
      if (($universal_id && $_domain->OID != $universal_id)) {
        continue;
      }

      // Si on l'a déjà identifié comme l'IPP on le supprime des IPP
      if ($id_number == CMbArray::get($data, "PI")) {
        continue;
      }

      $data["PI_Others"][$_domain->tag] = $id_number;
    }
  }

  /**
   * Get AN identifier
   *
   * @param DOMNode       $node   Node
   * @param array         $data   Data
   * @param CInteropActor $sender Sender
   *
   * @return void
   * @throws Exception
   */
  function getANIdentifier(DOMNode $node, &$data, CInteropActor $sender) {
    if (CHL7v2Message::$handle_mode === "simple") {
      $data["AN"] = $this->queryTextNode("CX.1", $node);

      return;
    }

    $control_identifier_type_code = CValue::read($sender->_configs, "control_identifier_type_code");

    $search_master_NDA = CValue::read($sender->_configs, "search_master_NDA");
    if ($search_master_NDA) {
      $domain = CDomain::getMasterDomain("CSejour", $sender->group_id);

      if ($domain->namespace_id != $this->queryTextNode("CX.4/HD.1", $node)) {
        return;
      }

      if ($control_identifier_type_code) {
        if ($this->queryTextNode("CX.5", $node) === "AN") {
          $data["AN"] = $this->queryTextNode("CX.1", $node);
        }
      }
      else {
        $data["AN"] = $this->queryTextNode("CX.1", $node);
      }

      return;
    }

    if ($control_identifier_type_code) {
      if ($this->queryTextNode("CX.5", $node) === "AN") {
        $data["AN"] = $this->queryTextNode("CX.1", $node);
      }
    }
    else {
      $data["AN"] = $this->queryTextNode("CX.1", $node);
    }
  }

  /**
   * Get AN mother identifier
   *
   * @param DOMNode $node Node
   *
   * @return string
   * @throws Exception
   */
  function getANMotherIdentifier(DOMNode $node) {
    $PID_21 = $this->queryNodes("PID.21", $node);
    foreach ($PID_21 as $_PID21) {
      if (CHL7v2Message::$handle_mode === "simple") {
        return $this->queryTextNode("CX.1", $_PID21);
      }
      else {
        if ($this->queryTextNode("CX.5", $_PID21) === "AN") {
          return $this->queryTextNode("CX.1", $_PID21);
        }
      }
    }

    return null;
  }

  /**
   * Get PI mother identifier
   *
   * @param DOMNode $node Node
   *
   * @return string|null
   * @throws Exception
   */
  function getPIMotherIdentifier(DOMNode $node) {
    $PID_21 = $this->queryNodes("PID.21", $node);
    foreach ($PID_21 as $_PID21) {
      if ($this->queryTextNode("CX.5", $_PID21) === "PI") {
        return $this->queryTextNode("CX.1", $_PID21);
      }
    }

    return null;
  }

  /**
   * Get VN identifier
   *
   * @param DOMNode $node Node
   * @param array   $data Data
   *
   * @return void
   * @throws Exception
   */
  function getVNIdentifiers(DOMNode $node, &$data) {
    if ($this->queryTextNode("CX.5", $node) === "VN") {
      $data["VN"] = $this->queryTextNode("CX.1", $node);
    }

    if (!CMbArray::get($data, "VN")) {
      if ($this->queryTextNode("CX.5", $node) === "RI") {
        $data["VN"] = $this->queryTextNode("CX.1", $node);
      }
    }
  }

  /**
   * Get RI identifiers
   *
   * @param DOMNode       $node   Node
   * @param array         $data   Data
   * @param CInteropActor $sender Sender
   *
   * @return void
   * @throws Exception
   */
  function getRIIdentifiers(DOMNode $node, &$data, CInteropActor $sender) {
    $control_identifier_type_code = CValue::read($sender->_configs, "control_identifier_type_code");

    // Notre propre RI
    $guid = "CGroups-$sender->group_id";
    if ($this->queryTextNode("CX.4/HD.1", $node) == CAppUI::conf("hl7 CHL7 assigning_authority_namespace_id" , $guid)
        || $this->queryTextNode("CX.4/HD.2", $node) == CAppUI::conf("hl7 CHL7 assigning_authority_universal_id", $guid)
    ) {
      if ($control_identifier_type_code && $this->queryTextNode("CX.5", $node) !== "RI") {
        return;
      }

      $data["RI"] = $this->queryTextNode("CX.1", $node);

      return;
    }

    // RI de l'expéditeur
    if ($this->queryTextNode("CX.4/HD.1", $node) == $sender->_configs["assigning_authority_namespace_id"]
        || $this->queryTextNode("CX.4/HD.2", $node) == $sender->_configs["assigning_authority_universal_id"]
    ) {
      if ($control_identifier_type_code && $this->queryTextNode("CX.5", $node) !== "RI") {
        return;
      }

      $data["RI_Sender"] = $this->queryTextNode("CX.1", $node);
      return;
    }

    // RI des autres systèmes
    if ($this->queryTextNode("CX.5", $node) === "RI") {
      $data["RI_Others"] = $this->queryTextNode("CX.1", $node);
    }
  }

  /**
   * Get NPA identifiers
   *
   * @param DOMNode       $node   Node
   * @param array         $data   Data
   * @param CInteropActor $sender Sender
   *
   * @return void
   * @throws Exception
   */
  function getNPAIdentifiers(DOMNode $node, &$data, CInteropActor $sender) {
    if (CHL7v2Message::$handle_mode === "simple") {
      $data["NPA"] = $this->queryTextNode("CX.1", $node);
    }
    else {
      if ($this->queryTextNode("CX.5", $node) === "AN") {
        $data["NPA"] = $this->queryTextNode("CX.1", $node);
      }

      if ($this->queryTextNode("CX.5", $node) === "PREAD") {
        $data["NPA"] = $this->queryTextNode("CX.1", $node);
      }
    }
  }

  /**
   * Get person identifiers
   *
   * @param string        $nodeName    Node name
   * @param DOMNode       $contextNode Node
   * @param CInteropActor $sender      Sender
   *
   * @return array
   * @throws Exception
   */
  function getPersonIdentifiers($nodeName, DOMNode $contextNode, CInteropActor $sender) {
    $data = array();
    
    foreach ($this->query($nodeName, $contextNode) as $_node) {
      // Gestion des identifiants pour Doctolib
      if (CMbArray::get($sender->_configs, "handle_doctolib") && CModule::getActive("doctolib")) {
        CSenderHL7v2Doctolib::getPersonIdentifiers($this, $_node, $data, $sender);

        continue;
      }

      // RI - Resource identifier
      $this->getRIIdentifiers($_node, $data, $sender);
      
      // PI - Patient internal identifier
      $this->getPIIdentifier($_node, $data, $sender);

      // Other PI - Patient internal identifier
      $this->getOtherPIIdentifiers($_node, $data, $sender);
      
      // INS-C - Identifiant national de santé calculé
      if ($this->queryTextNode("CX.5", $_node) === "INS-C") {
        $data["INSC"] = $this->queryTextNode("CX.1", $_node);
      } 
      
      // SS - Numéro de Sécurité Social
      if ($this->queryTextNode("CX.5", $_node) === "SS") {
        $data["SS"] = $this->queryTextNode("CX.1", $_node);
      } 
    }
    
    // AN - Patient Account Number (NDA)
    foreach ($this->query("PID.18", $contextNode) as $_node) {  
      $this->getANIdentifier($_node, $data, $sender);
    }

    return $data;
  }

  /**
   * Get admit identifiers
   *
   * @param DOMNode       $contextNode Node
   * @param CInteropActor $sender      Sender
   *
   * @return array
   * @throws Exception
   */
  function getAdmitIdentifiers(DOMNode $contextNode, CInteropActor $sender) {
    $data = array();
    
    // RI - Resource identifier 
    // VN - Visit Number
    // AN - On peut également retrouver le numéro de dossier dans ce champ
    $handle_NDA = CValue::read($sender->_configs, "handle_NDA");
    foreach ($this->query("PV1.19", $contextNode) as $_node) {
      switch ($handle_NDA) {
        case 'PV1_19':
          $this->getANIdentifier($_node, $data, $sender);
          break;
          
        default:
          // RI - Resource Identifier
          $this->getRIIdentifiers($_node, $data, $sender);

          // VN - Visit Number
          $this->getVNIdentifiers($_node, $data);
    
          break;
      }
    }

    $handle_PV1_50 = CValue::read($sender->_configs, "handle_PV1_50");
    switch ($handle_PV1_50) {
      // Il s'agit-là du sejour_id qui fait office de "NDA temporaire"
      case 'sejour_id':
        foreach ($this->query("PV1.50", $contextNode) as $_node) {
          if ($this->queryTextNode("CX.5", $_node) === "AN") {
            $data["RI"] = $this->queryTextNode("CX.1", $_node);
          }
        }

        break;

      default:
    }

    // PA - Preadmit Number
    if ($PV1_5 = $this->queryNode("PV1.5", $contextNode)) {
      $this->getNPAIdentifiers($PV1_5, $data, $sender);
    }
        
    return $data;
  }

  /**
   * Return the Object with the information of the medecin in the message
   *
   * @param DOMNode   $node   Node
   * @param CMbObject $object object
   * @param bool      $create Create doctor if not exist ?
   *
   * @return int|null|string
   * @throws Exception
   */
  function getDoctor(DOMNode $node, CMbObject $object, $create = true) {
    $type_id    = $this->queryTextNode("XCN.13", $node);
    $id         = $this->queryTextNode("XCN.1", $node);
    $last_name  = $this->queryTextNode("XCN.2/FN.1", $node);
    $first_name = $this->queryTextNode("XCN.3", $node);

    if ($type_id && $type_id == "EI") {
      return null;
    }

    $sender = $this->_ref_sender;
    switch ($type_id) {
      case "RPPS":
        $object->rpps = $id;
        break;

      case "ADELI":
        $object->adeli = $id;
        break;

      case "RI":
        // Notre propre RI
        $aauid = CAppUI::conf("hl7 CHL7 assigning_authority_universal_id", "CGroups-$sender->group_id");
        if ($this->queryTextNode("XCN.9/HD.2", $node) == $aauid) {
          return $id;
        }

      default:
        // Recherche du praticien par son idex
        $idex  = CIdSante400::getMatch($object->_class, $this->_ref_sender->_tag_mediuser, $id);
        if ($idex->_id) {
          return $idex->object_id;
        }

        if ($object instanceof CMediusers) {
          $object->_user_first_name = $first_name;
          $object->_user_last_name  = $last_name;
        }
        if ($object instanceof CMedecin) {
          $object->prenom = $first_name;
          $object->nom    = $last_name;
        }

        break;
    }

    // Cas où l'on a aucune information sur le médecin
    if (!$object->rpps
        && !$object->adeli
        && !$object->_id
        && (
          ($object instanceof CMediusers && !$object->_user_last_name) ||
          ($object instanceof CMedecin   && !$object->nom)
        )
    ) {
      return null;
    }

    if ($object instanceof CMedecin) {
      $object->loadMatchingObjectEsc();
      // Dans le cas où il n'est pas connu dans MB on le créé
      if (!$object->_id) {
        $object->store();
      }

      return $object->_id;
    }

    $ds = $object->getDS();

    if ($object instanceof CMediusers) {
      $ljoin = array();
      $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";

      $where   = array();
      $where["functions_mediboard.group_id"] = " = '$sender->group_id'";

      if ($object->rpps || $object->adeli) {
        if ($object->rpps) {
          $where[] = $ds->prepare("rpps = %", $object->rpps);
        }
        if ($object->adeli) {
          $where[] = $ds->prepare("adeli = %", $object->adeli);
        }

        // Dans le cas où le praticien recherché par son ADELI ou RPPS est multiple
        if ($object->countList($where, null, $ljoin) > 1) {
          $ljoin["users"] = "users_mediboard.user_id = users.user_id";
          $where[]        = $ds->prepare("users.user_last_name = %" , $last_name);
        }

        $object->loadObject($where, null, null, $ljoin);

        if ($object->_id) {
          return $object->_id;
        }
      }

      $user = new CUser;

      $ljoin = array();
      $ljoin["users_mediboard"]     = "users.user_id = users_mediboard.user_id";
      $ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";

      $where   = array();
      $where["functions_mediboard.group_id"] = " = '$sender->group_id'";
      $where[] = $ds->prepare("users.user_first_name = %", $first_name);
      $where[] = $ds->prepare("users.user_last_name = %" , $last_name);

      $order = "users.user_id ASC";
      if ($user->loadObject($where, $order, null, $ljoin)) {
        return $user->_id;
      }

      $object->_user_first_name = $first_name;
      $object->_user_last_name  = $last_name;

      if ($create) {
        return $this->createDoctor($object);
      }

      return null;
    }
  }

  /**
   * Create the mediuser
   *
   * @param CMediusers $mediuser mediuser
   *
   * @return int
   * @throws Exception
   */
  function createDoctor(CMediusers $mediuser) {
    $sender = $this->_ref_sender;

    $function = new CFunctions();
    $function->text = CAppUI::conf("hl7 importFunctionName");
    $function->group_id = $sender->group_id;
    $function->loadMatchingObjectEsc();
    if (!$function->_id) {
      $function->type            = "cabinet";
      $function->compta_partagee = 0;
      $function->color           = "ffffff";
      $function->store();
    }
    $mediuser->function_id = $function->_id;
    $mediuser->_user_username = CMbFieldSpec::randomString(array_merge(range('0', '9'), range('a', 'z'), range('A', 'Z')), 20);
    $mediuser->_user_password = CMbSecurity::getRandomPassword();
    $mediuser->_user_type = 13; // Medecin
    $mediuser->actif = CAppUI::conf("hl7 doctorActif") ? 1 : 0;

    $user = new CUser();
    $user->user_last_name   = $mediuser->_user_last_name;
    $user->user_first_name  = $mediuser->_user_first_name;
    // On recherche par le seek
    $users                  = $user->seek("$user->user_last_name $user->user_first_name");
    if (count($users) === 1) {
      $user = reset($users);
      $user->loadRefMediuser();
      $mediuser = $user->_ref_mediuser;
    }
    else {
      // Dernière recherche si le login est déjà existant
      $user = new CUser();
      $user->user_username = $mediuser->_user_username;
      if ($user->loadMatchingObject()) {
        // On affecte un username aléatoire
        $mediuser->_user_username .= rand(1, 10);
      }

      $mediuser->store();
    }

    return $mediuser->_id;
  }

  /**
   * Get content nodes
   *
   * @return array
   * @throws Exception
   */
  function getContentNodes() {
    $data  = array();

    $exchange_hl7v2 = $this->_ref_exchange_hl7v2;
    $sender       = $exchange_hl7v2->_ref_sender;
    $sender->loadConfigValues();
   
    $this->_ref_sender = $sender;
    
    $this->queryNode("EVN", null, $data, true);
    
    $PID = $this->queryNode("PID", null, $data, true);

    $data["personIdentifiers"] = $this->getPersonIdentifiers("PID.3", $PID, $sender);

    $this->queryNode("PD1", null, $data, true);
    
    return $data;
  }

  /**
   * Get AN number
   *
   * @param CInteropActor $sender Sender
   * @param array         $data   Data
   *
   * @return string
   */
  function getVenueAN(CInteropActor $sender, $data) {
    switch ($sender->_configs["handle_NDA"]) {
      case 'PV1_19':
        return CValue::read($data['admitIdentifiers'], "AN");

      default:
        return CValue::read($data['personIdentifiers'], "AN");
    }
  }

  /**
   * Get boolean
   *
   * @param bool $value Value
   *
   * @return int
   */
  function getBoolean($value) {
    return ($value === "Y") ? 1 : 0;
  }

  /**
   * Get phone
   *
   * @param string $string Value
   *
   * @return mixed
   */
  function getPhone($string) {
    return preg_replace("/[^0-9]/", "", $string);
  }

  /**
   * Get segement OBX
   *
   * @param DOMNode       $node   Node
   * @param CMbObject     $object Object
   * @param array         $data   Data
   * @param CInteropActor $sender sender
   * @param int           $set_id set_id
   *
   * @return void
   * @throws CHL7v2Exception
   */
  function getOBX(DOMNode $node, CMbObject $object, $data, CInteropActor $sender = null, $set_id = null) {
    $value_type = $this->queryTextNode("OBX.2", $node);

    switch ($value_type) {
      case 'NM':
        $type  = $this->queryTextNode("OBX.3/CE.2", $node);
        $value = (float)$this->queryTextNode("OBX.5", $node);

        $constante_medicale = new CConstantesMedicales();

        if ($object instanceof CSejour) {
          $constante_medicale->context_class = "CSejour";
          $constante_medicale->context_id    = $object->_id;
          $constante_medicale->patient_id    = $object->patient_id;
        }
        else if ($object instanceof CPatient) {
          $constante_medicale->context_class = "CPatient";
          $constante_medicale->context_id    = $object->_id;
          $constante_medicale->patient_id    = $object->_id;
        }

        $constante_medicale->datetime = $this->queryTextNode("EVN.2/TS.1", $data["EVN"]);
        $constante_medicale->loadMatchingObject();
        switch ($type) {
          case "WEIGHT":
            $constante_medicale->poids  = $value;
            break;

          case "HEIGHT":
            $constante_medicale->taille = $value;
            break;

          default:
        }
        $constante_medicale->_new_constantes_medicales = true;

        // Pour le moment pas de retour d'erreur dans l'acquittement
        $constante_medicale->store();
        break;
      case 'ED':
        if (!CMbArray::get($sender->_configs, "handle_OBX_photo_patient")) {
          return;
        }

        //Récupération du fichier et du type du fichier (basename)
        $observation  = $this->getObservationValue($node);

        $ed      = explode("^", $observation);
        $content = base64_decode(CMbArray::get($ed, 4));

        // Est ce qu'on a déjà le GUID du fichier dans le message HL7
        $file_guid = CMbArray::get($ed, 4);
        $guid = explode('-', $file_guid);

        if (CMbArray::get($guid, 0) && CMbArray::get($guid, 1)) {
          if (class_exists(CMbArray::get($guid, 0))) {
            $file = CMbObject::loadFromGuid($file_guid);
            if ($file->_id) {
              return;
            }
          }
        }

        $file = new CFile();
        $file->setObject($object);
        $file->file_name = "identite.jpg";
        $file->file_type = "image/jpeg";
        $file->loadMatchingObject();

        if (!$content) {
          return;
        }

        $file->file_date = CMbDT::dateTime();
        $file->doc_size  = strlen($content);

        $file->fillFields();
        $file->setContent($content);

        if ($msg = $file->store()) {
          return;
        }

        $exchange_hl7v2 = $this->_ref_exchange_hl7v2;

        // Suppression du base64 dans le message et remplacement par le GUID du CFile
        $hl7_message = new CHL7v2Message;
        $hl7_message->parse($exchange_hl7v2->_message);

        /** @var CHL7v2MessageXML $xml */
        $xml = $hl7_message->toXML(null, true);
        $xml = CHL7v2Message::setIdentifier(
          $xml, "//OBX[".$set_id."]", $file->_guid, "OBX.5", null, null, 5, "^"
        );

        $exchange_hl7v2->_message = $xml->toER7($hl7_message);
        $exchange_hl7v2->store();

        break;
      default:
    }
  }

  /**
   * Get observation file name
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   * @throws Exception
   */
  function getObservationFilename(DOMNode $node) {
    return $this->queryTextNode("OBX.3/CE.1", $node);
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
   * Get observation value
   *
   * @param DOMNode $node DOM node
   *
   * @return string
   * @throws Exception
   */
  function getObservationValue(DOMNode $node) {
    return $this->queryTextNode("OBX.5", $node);
  }

  /**
   * Handle event
   *
   * @param CHL7Acknowledgment $ack    Acknowledgment
   * @param CMbObject          $object Object
   * @param array              $data   Data
   *
   * @return void|string
   */
  function handle(CHL7Acknowledgment $ack = null, CMbObject $object = null, $data = array()) {
  }

  /**
   * Get event acknowledgment
   *
   * @param CHL7Event $event Event
   *
   * @return CHL7v2Acknowledgment|CHL7v2PatientDemographicsAndVisitResponse|CHL7v2ReceiveOrderMessageResponse
   */
  function getEventACK(CHL7Event $event) {
    // Pour l'acquittement du PDQ on retourne une réponse à la requête
    if ($this instanceof CHL7v2GeneratePatientDemographicsResponse) {
      return new CHL7v2PatientDemographicsAndVisitResponse($event);
    }

    // Pour l'acquittement du ORM on retourne un message ORR
    if ($this instanceof CHL7v2ReceiveOrderMessage) {
      return new CHL7v2ReceiveOrderMessageResponse($event);
    }

    // Génère un acquittement classique
    return new CHL7v2Acknowledgment($event);
  }

  /**
   * Verifies that the message is for this actor
   *
   * @param array         $data  Data
   * @param CInteropActor $actor Actor
   *
   * @return bool
   */
  function checkApplicationAndFacility($data, CInteropActor $actor) {
    if (empty($actor->_configs["check_receiving_application_facility"])) {
      return true;
    }

    return ($data['receiving_application'] == $actor->_configs["receiving_application"]) &&
           ($data['receiving_facility'] == $actor->_configs['receiving_facility']);
  }

  /**
   * Flatten the XML message to ER7
   * 
   * @param CHMessage $message  Source message
   * @param bool      $decorare Decorate output
   *
   * @return string
   * @throws CHL7v2Exception
   * @throws Exception
   */
  function toER7(CHMessage $message, $decorare = false) {    
    $node = $this->documentElement;
    
    $field_name   = null;
    $segment_name = null;
    $group_name   = null;
    $message_name = null;
    
    $sep_segment      = "\n";
    $sep_field        = null;
    $sep_repetition   = null;
    $sep_component    = null;
    $sep_subcomponent = null;

    $separators = "";
    $states = array();
    
    /** @var CHMessageDOMEntity $current_message */
    $current_message = null;
    
    /** @var CHMessageDOMEntity $current_segment */
    $current_segment = null;
    
    /** @var CHMessageDOMEntity $current_field */
    $current_field = null;
    
    /** @var CHMessageDOMEntity $current_component */
    $current_component = null;
    
    /** @var CHMessageDOMEntity $current_subcomponent */
    $current_subcomponent = null;
    
    /** @var string $next_state */
    $next_state = null;
    
    while ($node) {
      $node_name = $node->nodeName;
      
      $current_state = end($states);
      
      switch ($current_state) {
        // start
        case null:
          if (!preg_match('/^(\w{3}_\w{3}|ACK)$/', $node_name)) {
            throw new CHL7v2Exception(0, "'$node_name' n'est pas un message HL7");
          }

          $message_name = $node_name;
          $current_message = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_MESSAGE);

          $next_state = "message";
          break;
          
        case "message":
        case "group":
          if (preg_match('/^\w{3}_\w{3}\..+$/', $node_name)) {
            $group_name = $node_name;
            
            $current_segment = null;
            $current_field = null;
            $current_component = null;
            $current_subcomponent = null;

            $next_state = "group";
            break;
          }
          
          if (!preg_match('/^\w{3}$/', $node_name)) {
            throw new CHL7v2Exception(0, "'$node_name' n'est pas un segment HL7");
          }

          $segment_name = $node_name;
          $current_segment = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_SEGMENT, $current_message);
          
          // Add segment header as first field
          $segment_header = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_FIELD, $current_segment);
          $segment_header->value = $segment_name;

          $next_state = "segment";

          $last_field_index = 0;
          break;
          
        case "segment":
          $matches = null;
          if (!preg_match('/^\w{3}\.(\d+)$/', $node_name, $matches)) {
            throw new CHL7v2Exception(0, "'$node_name' n'est pas un field HL7");
          }

          $current_field = null;
          $current_component = null;
          $current_subcomponent = null;
          
          $current_field_index = (int)$matches[1];
          
          // Ajouter des champs vides dans le cas de numeros de champs non contigüs
          if ($current_field_index !== $last_field_index+1) {
            $dif = ($current_field_index - $last_field_index);
            for ($i = 1; $i < $dif; $i++) {
              $field_name = "$segment_name." . ($last_field_index + $i);
              
              $current_repetition = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_REPETITION);
              $current_segment->appendSubChild($current_repetition, $field_name, CHMessageDOMEntity::TYPE_FIELD);
              $current_repetition->value = "";
            }
          }
          
          $last_field_index = $current_field_index;

          $field_name = $node_name;

          $current_repetition = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_REPETITION);
          $current_field = $current_segment->appendSubChild($current_repetition, $field_name, CHMessageDOMEntity::TYPE_FIELD);

          $next_state = "field";
          break;
          
        case "field":
        case "component":
        case "subcomponent":
          if ($node_name === "#text") {
            if ($field_name === "MSH.1" || $field_name === "MSH.2") {
              $separators .= $node->nodeValue;
              
              if (isset($separators[0])) {
                $sep_field = $separators[0];
                CHMessageDOMEntity::setSeparator(CHMessageDOMEntity::TYPE_SEGMENT, $sep_field);
              }
              if (isset($separators[1])) {
                $sep_component = $separators[1];
                CHMessageDOMEntity::setSeparator(CHMessageDOMEntity::TYPE_REPETITION, $sep_component);
              }
              if (isset($separators[2])) {
                $sep_repetition = $separators[2];
                CHMessageDOMEntity::setSeparator(CHMessageDOMEntity::TYPE_FIELD, $sep_repetition);
              }
              if (isset($separators[4])) {
                $sep_subcomponent = $separators[4];
                CHMessageDOMEntity::setSeparator(CHMessageDOMEntity::TYPE_COMPONENT, $sep_subcomponent);
              }
              
              if ($sep_field) {
                $current_segment->glue = $sep_field;
              }
              
              if ($sep_repetition) {
                $current_field->glue = $sep_repetition;
              }
            }

            $current_entity = $current_subcomponent ?: $current_component ?: $current_repetition ?: $current_field;
            
            if ($field_name === "MSH.1") {
              // Remove first child of current segment for MSH
              if ($current_segment) {
                array_pop($current_segment->children);
              }
              
              $current_entity->value = "";
            }
            else {
              if (in_array($field_name, $message->getKeepOriginal())) {
                $current_entity->value = "$node->nodeValue";
              }
              else {
                $current_entity->value = $message->escape($node->nodeValue);
              }
            }
            break;
          }
          
          switch ($current_state) {
            default:
            case "field":
              $current_subcomponent = null;
              
              $next_state = "component";
              $current_component = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_COMPONENT, $current_repetition);
              break;
            case "component":
              $next_state = "subcomponent";
              $current_subcomponent = new CHMessageDOMEntity(CHMessageDOMEntity::TYPE_SUBCOMPONENT, $current_component);
              break;
            case "subcomponent":
              $next_state = "subcomponent";
              throw new Exception("Unexpected compoment in a subcomponent ($node_name)");
              break 2;
          }

          break;
          
        default:
          break;
      }

      // Algorithm from 
      // https://codereview.stackexchange.com/questions/28307/implementing-an-algorithm-that-walks-the-dom-without-recursion
      if ($child = $this->getFirstChildElement($node)) {
        $node = $child;
        
        // Down in the states stack
        $states[] = $next_state;
      }
      elseif ($sibling = $this->getFirstSiblingElement($node)) {
        $node = $sibling;
      }
      else {
        do {
          $node = $node->parentNode;
          
          // Up in the states stack
          array_pop($states);

          //if we are back at document.body, return!
          if ($node === $this->documentElement) {
            break;
          }
        } while (!($sibling = $this->getFirstSiblingElement($node)));
        
        $node = $sibling;
      }
    }

    CHMessageDOMEntity::$decorate = $decorare;
    
    return "$current_message";
  }

  /**
   * Get first DOMElement child
   * 
   * @param DOMNode $node
   *
   * @return DOMNode|null
   */
  function getFirstChildElement(DOMNode $node) {
    if (!$node->hasChildNodes()) {
      return null;
    }

    /** @var DOMNode $_childNode */
    foreach ($node->childNodes as $_childNode) {
      if ($_childNode->nodeType === XML_ELEMENT_NODE
        || ($_childNode->nodeType === XML_TEXT_NODE && trim($_childNode->nodeValue) !== '')
      ) {
        return $_childNode;
      }
    }
    
    return null;
  }

  /**
   * Get first DOMElement child
   * 
   * @param DOMNode $node
   *
   * @return DOMNode|null
   */
  function getFirstSiblingElement(DOMNode $node) {
    while ($node = $node->nextSibling) {
      if ($node->nodeType === XML_ELEMENT_NODE) {
        return $node;
      }
    }
    
    return null;
  }


  /**
   * Is ambigous NDA ?
   *
   * @param CSejour     $newVenue   Admit
   * @param array       $data       Admit identifiers
   * @param CIdSante400 $NDA        NDA
   * @param string      $error_code Error code
   *
   * @return bool
   * @throws Exception
   */
  function isAmbiguousNDA(CSejour $newVenue, $data, CIdSante400 $NDA, &$error_code) {
    if (!$NDA->_id) {
      $error_code = "E232";
      return true;
    }

    $admitIdentifiers = $data["admitIdentifiers"];

    $idex = new CIdSante400();
    $where = array();
    $where["id400"]        = " = '$NDA->id400'";
    $where["object_class"] = " = 'CSejour'";
    $where["tag"]          = " = '$NDA->tag'";
    $idexs = $idex->loadList($where);

    // Autres identifiants du séjour
    $venueRI = CValue::read($admitIdentifiers, "RI");
    $venueVN = CValue::read($admitIdentifiers, "VN");

    // Dans le cas où l'on a un seul idex, on le prend par défaut
    if (count($idexs) == 1) {
      $unique_idex = reset($idexs);
      $sejour = $unique_idex->loadTargetObject();
      $newVenue->cloneFrom($sejour);
      $newVenue->_id = $sejour->_id;

      return false;
    }

    $sender = $this->_ref_sender;
    $patient_search = null;
    /** @var CIdSante400 $_idex */
    foreach($idexs as $_idex) {
      /** @var CSejour $sejour */
      $sejour  = $_idex->loadTargetObject();
      $patient = $sejour->loadRefPatient();

      // Si l'idex retrouvé concerne le même séjour
      if ($newVenue->_id == $_idex->object_id) {
        $newVenue->cloneFrom($sejour);
        $newVenue->_id = $sejour->_id;

        return false;
      }

      if (!$patient_search) {
        $patient_search = $patient;
      }

      // Si le patient est différent sur un des séjours retrouvés
      if ($patient_search->_id != $patient->_id) {
        $error_code = "E233";
        return true;
      }

      // Si j'ai mon identifiant du séjour (RI)
      if ($venueRI && ($venueRI == $sejour->_id)) {
        $newVenue->cloneFrom($sejour);
        $newVenue->_id = $sejour->_id;

        return false;
      }

      // Si j'ai un identifiant de venue (VN)
      if ($venueVN) {
        $idexVN = CIdSante400::getMatchFor($sejour, $sender->_tag_visit_number);
        if ($idexVN->_id && ($venueVN == $idexVN->id400)) {
          $newVenue->cloneFrom($sejour);
          $newVenue->_id = $sejour->_id;

          return false;
        }
      }

      // Recherche par la date
      $PV1 = $data["PV1"];
      $PV2 = $data["PV2"];

      $entree_reelle = $this->queryTextNode("PV1.44", $PV1);
      $entree_prevue = $this->queryTextNode("PV2.8", $PV2);
      $entree = $entree_reelle ? : $entree_prevue;
      if ($sejour->entree <= $entree && $entree <= $sejour->sortie) {
        $newVenue->cloneFrom($sejour);
        $newVenue->_id = $sejour->_id;

        return false;
      }
    }

    return false;
  }

  /**
   * Get names
   *
   * @param DOMNode     $node       Node
   * @param CPatient    $newPatient Person
   * @param DOMNodeList $PID5       PID5
   *
   * @return void
   * @throws Exception
   */
  function getNames(DOMNode $node, CPatient $newPatient, DOMNodeList $PID5) {
    $fn1 = $this->queryTextNode("XPN.1/FN.1", $node);

    switch ($this->queryTextNode("XPN.7", $node)) {
      case "D":
        $newPatient->nom = $fn1;
        break;

      case "L":
        // Dans le cas où l'on a pas de nom de nom de naissance le legal name
        // est le nom du patient
        if ($PID5->length > 1) {
          $newPatient->nom_jeune_fille = $fn1;
        }
        else {
          $newPatient->nom = $fn1;
        }
        break;

      default:
        $newPatient->nom = $fn1;
    }
  }

  /**
   * Get first name
   *
   * @param DOMNode  $node       Node
   * @param CPatient $newPatient Person
   *
   * @return void
   * @throws Exception
   */
  function getFirstNames(DOMNode $node, CPatient $newPatient) {
    $xpn_2 = $this->queryTextNode("XPN.2", $node);
    $xpn_3 = $this->queryTextNode("XPN.3", $node);

    switch ($this->queryTextNode("XPN.7", $node)) {
      case "D":
        $newPatient->prenom_usuel = $xpn_2;
        break;

      case "L":
        $newPatient->prenom  = $xpn_2;
        $newPatient->prenoms = $xpn_3;
        break;

      default:
        $newPatient->prenom  = $xpn_2;
    }
  }
}
