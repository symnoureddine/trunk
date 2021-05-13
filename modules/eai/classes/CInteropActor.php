<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Eai\Transformations\CLinkActorSequence;
use Ox\Interop\Hl7\CHEvent;
use Ox\Interop\Smp\CSmp;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CLit;
use Ox\Mediboard\Hospi\CMovement;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CExchangeSource;

/**
 * Class CInteropActor 
 * Interoperability Actor
 */
class CInteropActor extends CMbObject {
  // DB Fields

  /** @var string */
  public $nom;

  /** @var string */
  public $libelle;

  /** @var string */
  public $group_id;

  /** @var int */
  public $actif;

  /** @var string */
  public $role;

  /** @var int */
  public $exchange_format_delayed;
  
  // Form fields
  /** @var int */
  public $_reachable;

  /** @var string */
  public $_parent_class;

  /** @var string */
  public $_tag_patient;

  /** @var string */
  public $_tag_sejour;

  /** @var string */
  public $_tag_mediuser;

  /** @var string */
  public $_tag_service;

  /** @var string */
  public $_tag_chambre;

  /** @var string */
  public $_tag_lit;

  /** @var string */
  public $_tag_movement;

  /** @var string */
  public $_tag_visit_number;

  /** @var string */
  public $_tag_consultation;

  /** @var string */
  public $_self_tag;

  /** @var array */
  public $_tags = array(); // All tags
  
  // Forward references
  /** @var CGroups */
  public $_ref_group;
  /** @var CDomain */
  public $_ref_domain;

  /** @var CExchangeSource[] */
  public $_ref_exchanges_sources;

  /** @var CExchangeDataFormat */
  public $_ref_last_exchange;

  /** @var int */
  public $_last_exchange_time;

  /** @var CMessageSupported[] */
  public $_ref_messages_supported;

  /** @var int */
  public $_count_messages_supported;

  /** @var array */
  public $_ref_msg_supported_family = array();

  /** @var CLinkActorSequence[] */
  public $_ref_eai_transformations;

  /** @var string */
  public $_type_echange;

  /** @var CExchangeDataFormat */
  public $_data_format;

  /**
   * @inheritDoc
   */
  function getProps() {
    $props = parent::getProps();
    $props["nom"]                     = "str notNull seekable|begin index";
    $props["libelle"]                 = "str";
    $props["group_id"]                = "ref notNull class|CGroups autocomplete|text";
    $props["actif"]                   = "bool notNull default|1";
    $props["role"]                    = "enum list|prod|qualif default|prod notNull";
    $props["exchange_format_delayed"] = "num min|0 default|60";
    
    $props["_reachable"]        = "bool";
    $props["_parent_class"]     = "str";

    $props["_self_tag"]         = "str";
    $props["_tag_patient"]      = "str";
    $props["_tag_sejour"]       = "str";
    $props["_tag_consultation"] = "str";
    $props["_tag_mediuser"]     = "str";
    $props["_tag_service"]      = "str";
    $props["_tag_chambre"]      = "str";
    $props["_tag_lit"]          = "str";
    $props["_tag_movement"]     = "str";
    $props["_tag_visit_number"] = "str";
    
    return $props;
  }

  /**
   * @inheritDoc
   */
  function updateFormFields() {
    parent::updateFormFields();
        
    $this->_view = $this->libelle ? $this->libelle : $this->nom;
    $this->_type_echange = $this->_class;

    $this->_self_tag          = $this->getTag($this->group_id);

    $this->_tag_patient       = CPatient::getTagIPP($this->group_id);  
    $this->_tag_sejour        = CSejour::getTagNDA($this->group_id);

    $this->_tag_consultation = CConsultation::getObjectTag($this->group_id);
    $this->_tag_mediuser     = CMediusers::getObjectTag($this->group_id);
    $this->_tag_service      = CService::getObjectTag($this->group_id);
    $this->_tag_chambre      = CChambre::getObjectTag($this->group_id);
    $this->_tag_lit          = CLit::getObjectTag($this->group_id);
    $this->_tag_movement     = CMovement::getObjectTag($this->group_id);
    $this->_tag_visit_number = CSmp::getObjectTag($this->group_id);
  }

  /**
   * Get actor tags
   *
   * @param bool $cache Cache
   *
   * @return array
   * @throws Exception
   */
  function getTags($cache = true) {
    $tags = array();
    
    foreach ($this->getSpecs() as $key => $spec) {
      if (strpos($key, "_tag_") === false) {
        continue;
      }
      
      $tags[$key] = $this->$key;
    }

    return $this->_tags = $tags;
  }

  /**
   * Get actor tag
   *
   * @param int $group_id Group
   *
   * @return string
   * @throws Exception
   */
  function getTag($group_id = null) {
    // Recherche de l'établissement
    $group = CGroups::get($group_id);
    if (!$group_id) {
      $group_id = $group->_id;
    }

    $cache = new Cache("$this->_class::getTag", array($group_id), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $ljoin["group_domain"] = "`group_domain`.`domain_id` = `domain`.`domain_id`";

    $where = array();
    $where["group_domain.group_id"] = " = '$group_id'";

    $where["domain.actor_class"]    = " = '$this->_class'";
    $where["domain.actor_id"]       = " = '$this->_id'";

    $domain = new CDomain();
    $domain->loadObject($where, null, null, $ljoin);

    return $cache->put($domain->tag, false);
  }

  /**
   * Get idex
   *
   * @param CMbObject $object Object
   *
   * @return CIdSante400
   * @throws Exception
   */
  function getIdex(CMbObject $object) {
    return CIdSante400::getMatchFor($object, $this->getTag($this->group_id, $this->_class));
  }

  /**
   * Load group forward reference
   *
   * @return CGroups
   * @throws Exception
   */
  function loadRefGroup() {
    return $this->_ref_group = $this->loadFwdRef("group_id", 1);
  }

  /**
   * Load user forward reference
   *
   * @return void
   */
  function loadRefUser() {
  }
  
  /**
   * Get exchanges sources
   *
   * @param bool $put_all_sources Put all sources
   *
   * @return void
   */
  function loadRefsExchangesSources($put_all_sources = false) {
  }

  /**
   * Return the fisrt element of exchangesSources
   *
   * @return mixed|null
   */
  function getFirstExchangesSources() {
    $this->loadRefsExchangesSources();
    if (!$this->_ref_exchanges_sources) {
      return null;
    }

    return reset($this->_ref_exchanges_sources);
  }

  /**
   * Load transformations
   *
   * @param array $where Additional where clauses
   *
   * @return CLinkActorSequence[]|CStoredObject[]
   * @throws Exception
   */
  function loadRefsEAITransformation($where = array()) {
    return $this->_ref_eai_transformations = $this->loadBackRefs(
      "actor_transformations", null, null, null, null, null, null, $where
    );
  }

  /**
   * Load domain
   *
   * @param array $where Additional where clauses
   *
   * @return CDomain|CStoredObject
   * @throws Exception
   */
  function loadRefDomain($where = array()) {
    $where["incrementer_id"] = "IS NULL";

    return $this->_ref_domain = $this->loadUniqueBackRef("domain", null, null, null, null, null, $where);
  }

  /**
   * Sender is reachable ?
   *
   * @param bool $put_all_sources Put all sources
   *
   * @return boolean reachable
   */
  function isReachable($put_all_sources = false) {
    if (!$this->_ref_exchanges_sources) {
      $this->loadRefsExchangesSources($put_all_sources);
    }
  }

  /**
   * Get all exchanges
   *
   * @return CExchangeDataFormat[]
   */
  function getAllExchanges() {
  }

  /**
   * Last message
   *
   * @return CExchangeDataFormat
   * @throws Exception
   */
  function lastMessage() {
    $last_exchange = null;

    // Dans le cas d'un destinataire on peut charger les échanges par la backref
    if ($this instanceof CInteropReceiver) {
      $last_exchange = $this->loadBackRefs(
        'echanges', "send_datetime DESC", "1", null, null, null, null, array("send_datetime IS NOT NULL")
      );
      if (!$last_exchange) {
        return;
      }

      $last_exchange = reset($last_exchange);
    }

    // Pour un expéditeur, il faut parcourir tous les formats
    if ($this instanceof CInteropSender) {
      foreach (CExchangeDataFormat::getAll(CExchangeDataFormat::class, false) as $key => $_exchange_class) {
        foreach (CApp::getChildClasses($_exchange_class, true) as $under_key => $_under_class) {
          /** @var CExchangeDataFormat $exchange */
          $exchange = new $_under_class;
          $exchange->sender_id    = $this->_id;
          $exchange->sender_class = $this->_class;

          $exchange->loadMatchingObject("send_datetime DESC");
          if ($exchange->_id) {
            $last_exchange = $exchange;

            continue 2;
          }
        }
      }
    }

    if (!$last_exchange) {
      return;
    }

    $this->_last_exchange_time = CMbDT::minutesRelative($last_exchange->send_datetime, CMbDT::dateTime());

    return $this->_ref_last_exchange = $last_exchange;
  }

  /**
   * Register actor ?
   *
   * @param string $name Actor name
   *
   * @return void
   * @throws Exception
   */
  function register($name) {
    $this->nom = $name;
    $this->loadMatchingObject();
  }

  /**
   * Count messages supported back reference collection
   *
   * @param array $where Clause where
   *
   * @return int
   * @throws Exception
   */
  function countMessagesSupported($where = array()) {
    return $this->_count_messages_supported = $this->countBackRefs("messages_supported", $where);
  }

  /**
   * Load messages supported back reference collection
   *
   * @return CMessageSupported[]|CStoredObject[]
   * @throws Exception
   */
  function loadRefsMessagesSupported() {
    return $this->_ref_messages_supported = $this->getMessagesSupported();
  }

  /**
   * Get messages supported
   *
   * @return CMessageSupported[]|CStoredObject[]
   *
   * @throws Exception
   */
  function getMessagesSupported() {
    $cache = new Cache("$this->_class::getMessagesSupported", $this->_guid, Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    $messages_supported = $this->loadBackRefs("messages_supported");

    return $cache->put($messages_supported);
  }

  /**
   * Is that the message is supported by this actor
   *
   * @param string $message Message
   *
   * @return bool
   * @throws Exception
   */
  function isMessageSupported($message) {
    $cache = new Cache("$this->_class::isMessageSupported", "$this->_guid-$message", Cache::INNER);
    if ($cache->exists()) {
      return $cache->get() > 0;
    }

    $msg_supported               = new CMessageSupported();
    $msg_supported->object_class = $this->_class;
    $msg_supported->object_id    = $this->_id;
    $msg_supported->message      = $message;
    $msg_supported->active       = 1;

    return $cache->put($msg_supported->countMatchingList()) > 0;
  }

  /**
   * Get messages supported by family
   *
   * @return array
   * @throws Exception
   */
  function getMessagesSupportedByFamily() {    
    $family = array();
    
    foreach (CExchangeDataFormat::getAll(CExchangeDataFormat::class, false) as $_data_format_class) {
      $_data_format = new $_data_format_class;
      $temp = $_data_format->getFamily();
      $family = array_merge($family, $temp);
    }
    
    if (empty($family)) {
      return $this->_ref_msg_supported_family;
    }

    $supported = $this->loadRefsMessagesSupported();
    foreach ($family as $_family => $_root_class) {
      $root  = new $_root_class;   

      foreach ($root->getEvenements() as $_evt => $_evt_class) {
        foreach ($supported as $_msg_supported) {
          if (!$_msg_supported->active) {
            continue;
          }

          if ($_msg_supported->message != $_evt_class) {
            continue;
          }
          
          $messages = $this->_spec->messages;
          if (isset($messages[$root->type])) {
            $this->_ref_msg_supported_family = array_merge($this->_ref_msg_supported_family, $messages[$root->type]);
            continue 3;
          }
        }
      }
    }

    return $this->_ref_msg_supported_family;
  }

  /**
   * Get format object handlers
   *
   * @return array
   */
  function getFormatObjectHandlers() {
    return array();
  }

  /**
   * Get objects
   *
   * @return array CInteropReceiver/CInteropSender collection
   * @throws Exception
   */
  static function getObjects() {
    $receiver = new CInteropReceiver();
    $sender   = new CInteropSender(); 
    
    return array(
      "CInteropReceiver" => $receiver->getObjects(),
      "CInteropSender"   => $sender->getObjects()
    );
  }

    /**
     * Count objects
     *
     * @return array CInteropReceiver/CInteropSender
     * @throws Exception
     */
    static function countObjects() {
        $receiver = new CInteropReceiver();
        $sender   = new CInteropSender();

        return array(
            "CInteropReceiver" => $receiver->countObjects(),
            "CInteropSender"   => $sender->countObjects()
        );
    }

  /**
   * Send event
   *
   * @param string    $evenement      Event
   * @param CMbObject $object         Objet Mediboard
   * @param String[]  $data           String[]
   * @param array     $headers        array
   * @param bool      $message_return No Send the message
   * @param bool      $soapVar        XML message ?
   *
   * @return bool|CHEvent
   */
  function sendEvent($evenement, $object, $data = array(), $headers = array(), $message_return = false, $soapVar = false) {
    // Si pas actif
    if (!$this->actif) {
      return false;
    }

    if ($this->role != CAppUI::conf("instance_role")) {
      return false;
    }

    return true;
  }

  /**
   * Get event
   *
   * @return bool
   */
  function getEvent() {
    return true;
  }

  /**
   * Get objects by events
   *
   * @param array            $events             Events name
   * @param CInteropReceiver $receiver           Receiver
   * @param bool             $only_current_group Load only receivers of the current group
   * @param string           $profil             Profil
   *
   * @return array Receivers supported
   * @throws Exception
   */
  static function getObjectsBySupportedEvents($events = array(), CInteropReceiver $receiver = null,
      $only_current_group = false, $profil = null
  ) {
    $receivers = array();
    $group_id  = CGroups::loadCurrent()->_id;

    foreach ($events as $_event) {
      $msg_supported       = new CMessageSupported();
      $msg_supported_table = $msg_supported->_spec->table;

      $where                                 = array();
      $where["$msg_supported_table.message"] = " = '$_event'";
      if ($profil) {
        $where["$msg_supported_table.profil"] = " = '$profil'";
      }
      $where["$msg_supported_table.active"] = " = '1'";

      $ljoin = array();
      if ($receiver) {
        $table         = $receiver->_spec->table;
        $key           = $receiver->_spec->key;
        $ljoin[$table] = "$table.$key = message_supported.object_id";

        $where["$msg_supported_table.object_class"] = " = '$receiver->_class'";
        if ($receiver->_id) {
          $where["$msg_supported_table.object_id"] = " = '$receiver->_id'";
        }

        if ($only_current_group) {
          $where["$table.group_id"] = "= '$group_id'";
        }

        $where["$table.actif"] = " = '1'";
      }

      if (!$msg_supported->loadObject($where, null, null, $ljoin)) {
        $receivers[$_event] = null;

        return $receivers;
      }

      $messages = $msg_supported->loadList($where, "object_class", null, null, $ljoin);

      foreach ($messages as $_message) {
        /** @var CInteropReceiver $receiver_found */
        $receiver_found = CMbObject::loadFromGuid("$_message->object_class-$_message->object_id");
        if (!$receiver_found->actif || $only_current_group && $receiver_found->group_id != $group_id) {
          continue;
        }

        $receiver_found->loadRefGroup();
        $receiver_found->isReachable();

        $receivers[$_event][] = $receiver_found;
      }
    }

    return $receivers;
  }
}
