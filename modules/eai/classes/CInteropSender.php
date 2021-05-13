<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Exception;
use Ox\AppFine\Client\CAppFineClient;
use Ox\AppFine\Server\CAppFineServer;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Hl7\CHL7;
use Ox\Interop\Hl7\CHL7Config;
use Ox\Interop\Hprimxml\CHPrimXML;
use Ox\Interop\Hprimxml\CHprimXMLConfig;
use Ox\Interop\Phast\CPhast;
use Ox\Interop\Phast\CPhastConfig;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Class CInteropSender
 * Interoperability Sender
 */
class CInteropSender extends CInteropActor
{
    public $user_id;
    public $save_unsupported_message;

    public $response;

    // Form fields
    public $_tag_hprimxml;
    public $_tag_phast;
    public $_tag_hl7;
    public $_tag_fhir;
    public $_tag_appFine;
    public $_tag_appFine_evenement_medical;
    public $_tag_appFine_responsable;

    // Forward references
    /** @var CMediusers $_ref_user */
    public $_ref_user;
    /** @var CObjectToInteropSender[] $_ref_object_links */
    public $_ref_object_links;
    /** @var CEAIRoute[] $_ref_routes */
    public $_ref_routes;

    /** @var CHL7Config */
    public $_ref_config_hl7;
    /** @var CHprimXMLConfig */
    public $_ref_config_hprim;
    /** @var CPhastConfig */
    public $_ref_config_phast;

    /**
     * @see parent::updateFormFields
     */
    function updateFormFields()
    {
        parent::updateFormFields();

        $this->_parent_class = "CInteropSender";

        if (CModule::getActive("hprimxml")) {
            $this->_tag_hprimxml = CHPrimXML::getObjectTag($this->group_id);
        }

        if (CModule::getActive("phast")) {
            $this->_tag_phast = CPhast::getTagPhast($this->group_id);
        }

        if (CModule::getActive("hl7")) {
            $this->_tag_hl7 = CHL7::getObjectTag($this->group_id);
        }

        if (CModule::getActive("fhir")) {
            $this->_tag_fhir = CFHIR::getObjectTag($this->group_id);
        }

        if (CModule::getActive("appFineClient")) {
            $this->_tag_appFine             = CAppFineClient::getObjectTagAppFine($this->group_id);
            $this->_tag_appFine_responsable = CAppFineClient::getObjectTagResponsableAppFine($this->group_id);
        }

        if (CModule::getActive("appFine")) {
            $this->_tag_appFine                   = CAppFineServer::getObjectTagAppFine($this->group_id);
            $this->_tag_appFine_evenement_medical = CAppFineServer::getObjectTagEvenementMedicalAppFine(
                $this->group_id
            );
        }
    }

    /**
     * @see parent::getProps
     */
    function getProps()
    {
        $props                             = parent::getProps();
        $props["user_id"]                  = "ref class|CMediusers";
        $props["save_unsupported_message"] = "bool default|1";
        $props["response"]                 = "enum list|none|auto_generate_before|postprocessor default|none";

        $props["_tag_hprimxml"]                  = "str";
        $props["_tag_phast"]                     = "str";
        $props["_tag_hl7"]                       = "str";
        $props["_tag_appFine"]                   = "str";
        $props["_tag_appFine_evenement_medical"] = "str";
        $props["_tag_appFine_responsable"]       = "str";

        return $props;
    }

    /**
     * @see parent::getSpec
     */
    function getSpec()
    {
        $spec                  = parent::getSpec();
        $spec->uniques["user"] = ["user_id"];

        return $spec;
    }

    /**
     * Load object links
     *
     * @return CObjectToInteropSender[]|CStoredObject[]
     * @throws Exception
     */
    function loadRefsObjectLinks()
    {
        if ($this->_ref_object_links) {
            return $this->_ref_object_links;
        }

        return $this->_ref_object_links = $this->loadBackRefs("object_links");
    }

    /**
     * @see parent::loadRefUser
     */
    function loadRefUser()
    {
        return $this->_ref_user = $this->loadFwdRef("user_id", 1);
    }

    /**
     * Load routes
     *
     * @param array $where Clause where
     *
     * @return CEAIRoute[]|CStoredObject[]
     * @throws Exception
     */
    function loadRefsRoutes($where = [])
    {
        return $this->_ref_routes = $this->loadBackRefs("routes_sender", null, null, null, null, null, null, $where);
    }

    /**
     * Get child senders
     *
     * @return array CInteropSender collection
     * @throws Exception
     */
    static function getChildSenders()
    {
        return CApp::getChildClasses(CInteropSender::class, true, true);
    }

    /**
     * Count objects
     *
     * @return array
     * @throws Exception
     */
    public static function countObjects(): array
    {
        $objects = [];
        foreach (self::getChildSenders() as $_interop_sender) {
            $itemSender = new $_interop_sender();

            // Récupération de la liste des senders
            $where = [];
            $objects[$_interop_sender]['total'] = $itemSender->countList($where);
            $where["actif"] = " = '1'";
            $where["role"] = " = '" . CAppUI::conf('instance_role') . "'";
            $objects[$_interop_sender]['total_actif'] = $itemSender->countList($where);
        }

        return $objects;
    }

    /**
     * Get objects
     *
     * @return array CInteropSender collection
     * @throws Exception
     */
    static function getObjects()
    {
        $objects = [];
        foreach (self::getChildSenders() as $_interop_sender) {
            $itemSender = new $_interop_sender;

            // Récupération de la liste des destinataires
            $where                     = [];
            $order                     = "group_id ASC, libelle ASC, nom ASC";
            $objects[$_interop_sender] = $itemSender->loadList($where, $order);
            if (!is_array($objects[$_interop_sender])) {
                continue;
            }
            foreach ($objects[$_interop_sender] as $_sender) {
                $_sender->loadRefGroup();
                $_sender->isReachable();
            }
        }

        return $objects;
    }

    /**
     * @param string $class
     * @param bool   $only_active
     * @param bool   $only_instance
     *
     * @return array
     */
    public function getObjectsByClass(string $class, bool $only_active = true, bool $only_instance = true): array
    {
        $itemSender = new $class();

        // Récupération de la liste des senders
        $where                     = [];
        if ($only_active) {
            $where["actif"] = " = '1'";
        }

        if ($only_instance) {
            $where["role"]   = " = '" . CAppUI::conf("instance_role") . "'";
        }

        $order                     = "group_id ASC, libelle ASC, nom ASC";
        $objects = $itemSender->loadList($where, $order);
        if (!is_array($objects)) {
            return $objects;
        }
        foreach ($objects as $_sender) {
            $_sender->loadRefGroup();
            $_sender->loadRefsExchangesSources();
        }

        return $objects;
    }

    /**
     * Read
     *
     * @return void
     */
    function read()
    {
    }

    /**
     * Get configs
     *
     * @param CExchangeDataFormat $data_format Exchange
     *
     * @return void
     */
    function getConfigs(CExchangeDataFormat $data_format)
    {
        $data_format->getConfigs($this->_guid);
        $format_config = $data_format->_configs_format;

        if (!isset($format_config->_id)) {
            return;
        }

        foreach ($format_config->getConfigFields() as $_config_field) {
            $this->_configs[$_config_field] = $format_config->$_config_field;
        }
    }

    /**
     * Get all exchanges
     *
     * @return CExchangeDataFormat[]
     * @throws Exception
     */
    function getAllExchanges()
    {
        foreach (CExchangeDataFormat::getAll(CExchangeDataFormat::class, false) as $key => $_exchange_class) {
            foreach (CApp::getChildClasses($_exchange_class, true, true) as $under_key => $_under_class) {
                /** @var CExchangeDataFormat $exchange */
                $exchange               = new $_under_class;
                $exchange->sender_id    = $this->_id;
                $exchange->sender_class = $this->_class;
            }
            if ($_exchange_class == "CExchangeAny") {
                $class = new CExchangeAny();
            }
        }
    }

    /**
     * Return collection HL7 config
     *
     * @return CHL7Config|CStoredObject
     * @throws Exception
     */
    function loadBackRefConfigHL7()
    {
        return $this->_ref_config_hl7 = $this->loadUniqueBackRef("config_hl7");
    }

    /**
     * Return collection Hprim config
     *
     * @return CHprimXMLConfig|CStoredObject
     * @throws Exception
     */
    function loadBackRefConfigHprimXML()
    {
        return $this->_ref_config_hprim = $this->loadUniqueBackRef("config_hprimxml");
    }

    /**
     * Return collection Hprim config
     *
     * @return CPhastConfig|CStoredObject
     * @throws Exception
     */
    function loadBackRefConfigPhast()
    {
        return $this->_ref_config_phast = $this->loadUniqueBackRef("config_phast");
    }
}
