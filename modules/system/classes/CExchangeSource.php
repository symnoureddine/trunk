<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Exception;
use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Chronometer;
use Ox\Core\CLogger;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CMbSecurity;
use Ox\Core\Module\CModule;
use Ox\Interop\Eai\CExchangeDataFormat;
use ReflectionClass;
use Throwable;

/**
 * Exchange Source
 */
class CExchangeSource extends CMbObject
{
    /** @var string Source type */
    public const TYPE = '';

    public static $typeToClass = [
        "sftp"        => "CSourceSFTP",
        "ftp"         => "CSourceFTP",
        "soap"        => "CSourceSOAP",
        "smtp"        => "CSourceSMTP",
        "pop"         => "CSourcePOP",
        "file_system" => "CSourceFileSystem",
        "http"        => "CSourceHTTP",
        "syslog"      => "CSyslogSource",
    ];

    //multi instance sources (more than one can run at the same time)
    public static $multi_instance = [
        "CSourcePOP",
        "CSourceSMTP",
    ];

    // DB Fields
    public $name;
    public $role;
    public $host;
    public $user;
    public $password;
    public $iv;
    public $type_echange;
    public $active;
    public $loggable;
    public $libelle;

    // Behaviour Fields
    public $_client;
    public $_data;
    public $_args_list    = false;
    public $_allowed_instances;
    public $_wanted_type;
    public $_incompatible = false;
    public $_reachable;
    public $_message;
    public $_response_time;
    public $_all_source   = [];
    public $_receive_filename;
    public $_acquittement;
    public $_count_exchange;

    public $_target_object;

    /** @var CExchangeDataFormat $_exchange_data_format */
    public $_exchange_data_format;

    public static $call_traces = [];

    /**
     * Initialize & start call_traces chronometer
     *
     * @return void
     */
    public function startCallTrace()
    {
        $_key_trace = static::class;
        if (array_key_exists($_key_trace, static::$call_traces) === false) {
            self::$call_traces[$_key_trace] = new Chronometer();
        }
        self::$call_traces[$_key_trace]->start();
    }

    /**
     * Stop call_traces chronometer
     *
     * @return void
     */
    public function stopCallTrace()
    {
        $_key_trace = static::class;
        static::$call_traces[$_key_trace]->stop();
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props                 = parent::getProps();
        $props["name"]         = "str notNull";
        $props["role"]         = "enum list|prod|qualif default|qualif notNull";
        $props["host"]         = "text notNull autocomplete";
        $props["user"]         = "str";
        $props["password"]     = "password randomizable show|0 loggable|0";
        $props["iv"]           = "str show|0 loggable|0";
        $props["type_echange"] = "str protected";
        $props["active"]       = "bool default|1 notNull";
        $props["loggable"]     = "bool default|0 notNull";
        $props["libelle"]      = "str";

        $props["_incompatible"]  = "bool";
        $props["_reachable"]     = "enum list|0|1|2 default|0";
        $props["_response_time"] = "float";

        return $props;
    }

    /**
     * Extend classes
     *
     * @return void
     */
    static function addExternalSources()
    {
        if (CModule::getActive("hl7")) {
            self::$typeToClass["mllp"] = "CSourceMLLP";
        }

        if (CModule::getActive("dicom")) {
            self::$typeToClass["dicom"] = "CSourceDicom";
        }

        if (CModule::getActive("mssante")) {
            self::$typeToClass["mssante"] = "CSourceMSSante";
            self::$multi_instance[]       = "CSourceMSSante";
        }

        if (CModule::getActive('oxPyxvital')) {
            self::$typeToClass['pyxvital'] = 'CPyxvitalSOAPSource';
        }
    }

    /**
     * @inheritDoc
     */
    function initialize()
    {
        parent::initialize();

        self::addExternalSources();
    }

    /**
     * @inheritdoc
     */
    function check()
    {
        $source = self::get($this->name, null, true);

        if ($source->_id && ($source->_id != $this->_id)) {
            $this->active = 0;
        }

        return parent::check();
    }

    /**
     * @inheritdoc
     */
    function store()
    {
        $this->completeField("name");

        if ($this->password === "") {
            $this->password = null;
        } else {
            if ($this->fieldModified("password") || !$this->_id) {
                $this->password = $this->encryptString();
            }
        }

        $this->updateEncryptedFields();

        return parent::store();
    }

    /**
     * Return the array of exchange classes
     *
     * @return array
     */
    static function getExchangeClasses()
    {
        self::addExternalSources();

        return self::$typeToClass;
    }

    /**
     * Return the exchange object
     *
     * @param CExchangeSource $exchange_source Name of the exchange source
     * @param array()         $type            Always null
     *
     * @return array|null
     */
    static function getObjects(CExchangeSource $exchange_source, $type = [])
    {
        if (!$type || (count($type) == 1 && $exchange_source->_class != "CExchangeSource")) {
            return null;
        }

        $name         = $exchange_source->name;
        $type_echange = $exchange_source->type_echange;

        $exchange_objects = [];
        foreach ($type as $_class) {
            /** @var CExchangeSource $object */
            $object = new $_class;

            if (!$object->_ref_module) {
                continue;
            }
            $object->name = $name;
            $object->loadMatchingObject();
            $object->type_echange = $type_echange;

            $exchange_objects[$_class] = $object;
        }

        return $exchange_objects;
    }

    /**
     * Get target object from source name
     *
     * @return CMbObject|null
     */
    function getTargetObject()
    {
        preg_match("/C[\w]+-\d+/", $this->name, $matches);

        $object_guid = CMbArray::get($matches, 0);
        if (!$object_guid) {
            return null;
        }

        return $this->_target_object = CMbObject::loadFromGuid($object_guid);
    }

    /**
     * Return all objects for exchange
     *
     * @param bool $only_active Seulement les sources actives
     *
     * @return array
     */
    static function getAllObjects($only_active = true)
    {
        $exchange_objects = [];
        $classes          = self::getExchangeClasses();
        unset($classes["syslog"]);
        unset($classes["smtp"]);
        unset($classes["pop"]);

        foreach ($classes as $_class) {
            /** @var CExchangeSource $object */
            $object = new $_class;

            if (!$object->_ref_module) {
                continue;
            }
            $where = [];
            if ($only_active) {
                $where["active"] = "= '1'";
            }
            $where["role"] = " = '" . CAppUI::conf("instance_role") . "'";

            $objects = $object->loadList($where);
            if (!$objects) {
                continue;
            }

            $exchange_objects[$_class] = $objects;
        }

        return $exchange_objects;
    }

    /**
     * Get the exchange source
     *
     * @param string            $name            Nom
     * @param string|array|null $type            Type de la source (FTP, SOAP, ...)
     * @param bool              $override        Charger les autres sources
     * @param string            $type_echange    Type de l'échange
     * @param bool              $only_active     Seulement la source active
     * @param bool              $put_all_sources Charger toutes les sources
     * @param boolean           $use_cache       Utiliser le cache
     *
     * @return CExchangeSource
     */
    static function get(
        $name,
        $type = null,
        $override = false,
        $type_echange = null,
        $only_active = true,
        $put_all_sources = false,
        $use_cache = true
    ) {
        $args = func_get_args();
        foreach ($args as $key => $_arg) {
            if (is_array($_arg)) {
                $_arg = implode("-", $_arg);
            }

            $args[$key] = $_arg;
        }

        if ($use_cache) {
            $cache = new Cache(__METHOD__, $args, Cache::INNER);
            if ($cache->exists()) {
                return $cache->get();
            }
        }

        $exchange_classes = self::getExchangeClasses();

        // On passe juste un type de source
        $source_type = null;
        if ($type && !is_array($type)) {
            $source_type = $type;
            $type        = [$type];
        }

        if ($type) {
            $type             = array_fill_keys($type, $type);
            $exchange_classes = array_intersect_key($exchange_classes, $type);
        }

        foreach ($exchange_classes as $_class_key => $_class) {
            /** @var CExchangeSource $exchange_source */
            $exchange_source = new $_class();

            if ($only_active && !$put_all_sources) {
                $exchange_source->active = 1;
            }

            $exchange_source->name = $name;
            $exchange_source->loadMatchingObject();

            if ($exchange_source->_id) {
                $exchange_source->_wanted_type       = $_class_key;
                $exchange_source->_allowed_instances = self::getObjects($exchange_source, $exchange_classes);
                if ($exchange_source->role != CAppUI::conf("instance_role")) {
                    if (!$override) {
                        $incompatible_source                = new $exchange_source->_class();
                        $incompatible_source->name          = $exchange_source->name;
                        $incompatible_source->_incompatible = true;
                        if (PHP_SAPI !== 'cli') {
                            CAppUI::displayAjaxMsg("CExchangeSource-_incompatible", UI_MSG_ERROR);
                        }

                        return $incompatible_source;
                    }
                    $exchange_source->_incompatible = true;
                }

                return $use_cache ? $cache->put($exchange_source, false) : $exchange_source;
            }
        }

        $source = new CExchangeSource();
        if ($source_type && isset(self::$typeToClass[$source_type])) {
            $source = new self::$typeToClass[$source_type]();
        }

        $source->name               = $name;
        $source->type_echange       = $type_echange;
        $source->_wanted_type       = key($exchange_classes);
        $source->_allowed_instances = self::getObjects($source, $exchange_classes);

        return $use_cache ? $cache->put($source, false) : $source;
    }

    /**
     * Encrypt fields
     *
     * @return void
     */
    function updateEncryptedFields()
    {
    }

    /**
     * Encrypt
     *
     * @param string $pwd      Password
     * @param string $iv_field Initialisation vector field
     *
     * @return null|string
     */
    function encryptString($pwd = null, $iv_field = "iv")
    {
        if (is_null($pwd)) {
            $pwd = $this->password;
        }

        try {
            $master_key = CApp::getAppMasterKey();

            $iv                = CMbSecurity::generateIV(16);
            $this->{$iv_field} = $iv;

            return CMbSecurity::encrypt(CMbSecurity::AES, CMbSecurity::CTR, $master_key, $pwd, $iv);
        } catch (Throwable $e) {
            return $pwd;
        }
    }

    /**
     * Get password
     *
     * @param string $pwd      Password
     * @param string $iv_field Initialisation vector field
     *
     * @return null|string
     */
    function getPassword($pwd = null, $iv_field = "iv")
    {
        if (is_null($pwd)) {
            $pwd = $this->password;
            if (!$this->password) {
                return "";
            }
        }

        try {
            $master_key = CApp::getAppMasterKey();

            $iv_to_use = $this->{$iv_field};

            if (!$iv_to_use) {
                $clear = $pwd;
                $this->store();

                return $clear;
            }

            return CMbSecurity::decrypt(CMbSecurity::AES, CMbSecurity::CTR, $master_key, $pwd, $iv_to_use);
        } catch (Throwable $e) {
            return $pwd;
        }
    }

    /**
     * Set data
     *
     * @param array               $data     Data
     * @param bool                $argsList Args list
     * @param CExchangeDataFormat $exchange Exchange
     *
     * @return void
     */
    function setData($data, $argsList = false, CExchangeDataFormat $exchange = null)
    {
        $this->_args_list            = $argsList;
        $this->_data                 = $data;
        $this->_exchange_data_format = $exchange;
    }

    /**
     * Get data
     *
     * @param string $path Path
     *
     * @return string
     */
    function getData($path)
    {
    }

    /**
     * Delete file
     *
     * @param string $path Path
     *
     * @return string
     */
    function delFile($path)
    {
    }

    /**
     * Send
     */
    function send()
    {
    }

    /**
     * Get acknowledgment
     *
     * @return string|array
     */
    function getACQ()
    {
        return $this->_acquittement;
    }

    /**
     * Receive one
     *
     * @return string
     */
    function receiveOne()
    {
    }

    /**
     * Receive
     *
     * @return string
     */
    function receive()
    {
    }

    /**
     * Rename file
     *
     * @param string $oldname Old name
     * @param string $newname New name
     *
     * @return mixed
     */
    function renameFile($oldname, $newname)
    {
    }

    /**
     * Change directory
     *
     * @param string $directory_name Directory name
     *
     * @return mixed
     */
    function changeDirectory($directory_name)
    {
    }

    /**
     * Create directory
     *
     * @param string $directory_name Directory name
     *
     * @return mixed
     */
    function createDirectory($directory_name)
    {
    }

    /**
     * Get current directory
     *
     * @param string $directory Directory name
     *
     * @return string
     */
    function getCurrentDirectory($directory = null)
    {
    }

    /**
     * Get last directory
     *
     * @param string $current_directory Current directory
     *
     * @return string
     */
    function getListDirectory($current_directory)
    {
    }

    /**
     * Get files details
     *
     * @param string $directory Directory name
     *
     * @return array
     */
    function getListFiles($directory)
    {
        return [];
    }

    /**
     * Get files details
     *
     * @param string $directory Directory name
     *
     * @return string
     */
    function getListFilesDetails($directory)
    {
    }

    /**
     * Add file
     *
     * @param string $file      File
     * @param string $file_name Filename
     * @param string $directory Directory
     *
     * @return bool|void
     */
    function addFile($file, $file_name, $directory)
    {
    }

    /**
     * Get file size
     *
     * @param string $file_name Filename
     * @param bool   $full_path Is full path ?
     *
     * @return bool|void
     */
    function getSize($file_name, $full_path = false)
    {
    }

    /**
     * Add file
     *
     * @param string $file      File
     * @param string $file_name Filename
     * @param string $directory Directory
     *
     * @return bool|void
     */
    function getFile($file, $file_name, $directory)
    {
    }

    /**
     * Source is reachable ?
     *
     * @return boolean reachable
     */
    function isReachable()
    {
        $this->_reachable = 0;
        if (!$this->active) {
            $this->_reachable = 1;
            $this->_message   = CAppUI::tr("CExchangeSource_no-active", $this->host);

            return;
        }

        if (!$this->isReachableSource()) {
            return;
        }

        if (!$this->isAuthentificate()) {
            return;
        }

        $this->_reachable = 2;
        $this->_message   = CAppUI::tr("$this->_class-reachable-source", $this->host);
    }

    /**
     * Authenticated user on source?
     *
     * @return boolean|void
     */
    function isReachableSource()
    {
    }

    /**
     * Source is authe
     *
     * @return boolean|void
     */
    function isAuthentificate()
    {
    }

    /**
     * Get response time
     *
     * @return int|void
     */
    function getResponseTime()
    {
    }

    /**
     * Get error
     *
     * @return string|void
     */
    function getError()
    {
    }

    /**
     * Get child exchanges
     *
     * @return string[] Data format classes collection
     * @throws Exception
     */
    static function getAll()
    {
        $sources = CApp::getChildClasses(CExchangeSource::class, true, true);

        return array_filter(
            $sources,
            function ($v) {
                $s = new $v();

                return ($s->_spec->key);
            }
        );
    }
}
