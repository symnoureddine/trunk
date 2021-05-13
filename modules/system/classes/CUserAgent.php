<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Exception;
use Ox\Core\CApp;
use Ox\Core\CLogger;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CValue;
use OxBrowscap\BrowscapFactory;

/**
 * User agent
 */
class CUserAgent extends CMbObject
{
    static $supported_browsers = [
        "Firefox" => "35.0",
        "Chrome"  => "41.0",
        "IE"      => "10.0",
        "Safari"  => "9.0",
    ];

    static $browser_names = [
        "Firefox",
        "Chrome",
        "IE",
        "Opera",
        "Opera Mini",
        "Safari",
        "Android",
        "Konqueror",
        "SeaMonkey",
        "Iceweasel",
    ];

    static $browser_code_names = [
        "Firefox" => "firefox",
        "Chrome"  => "chrome",
        "IE"      => "msie",
        "Opera"   => "opera",
        "Safari"  => "safari",
    ];

    static $platform_names = [
        "WinNT",
        "Win2000",
        "WinXP",
        "WinVista",
        "Win7",
        "Win8",
        "Win8.1",
        "Linux",
        "MacOSX",
        "iOS",
        "Android",
        "ChromeOS",
        "unknown",
    ];

    static $device_names = [
        "PC",
        "Android",
        "iPhone",
        "iPad",
        "Nexus 4",
        "Blackberry",
        "general Mobile Device",
        "unknown",
    ];

    static $device_makers = [
        "Various",
        "Apple",
        "Samsung",
        "HTC",
        "LG",
        "SonyEricsson",
        "RIM",
        "Google",
        "Microsoft",
        "unknown",
    ];

    public $user_agent_id;

    public $user_agent_string;

    public $browser_name;
    public $browser_version;

    public $platform_name;
    public $platform_version;

    public $device_name;
    public $device_maker;
    public $device_type; // Mobile Device, Mobile Phone, Desktop, Tablet
    public $pointing_method; // mouse, unknown, touchscreen

    public $_obsolete;
    public $_badly_detected;

    /** @var  CUserAuthentication[] */
    public $_ref_user_authentications;

    /**
     * Executed prior to any serialization of the object
     *
     * @return array Array of field names to be serialized
     */
    function __sleep()
    {
        return [
            $this->_spec->key,
            "user_agent_string",

            "browser_name",
            "browser_version",

            "platform_name",
            "platform_version",

            "device_name",
            "device_type",
            "device_maker",
            "pointing_method",
        ];
    }

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = "user_agent";
        $spec->key   = "user_agent_id";

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props                      = parent::getProps();
        $props["user_agent_string"] = "str notNull";

        $props["browser_name"]    = "str";
        $props["browser_version"] = "str";

        $props["platform_name"]    = "str";
        $props["platform_version"] = "str";

        $props["device_name"]     = "str";
        $props["device_type"]     = "enum notNull list|desktop|mobile|tablet|unknown default|unknown";
        $props["device_maker"]    = "str";
        $props["pointing_method"] = "enum notNull list|mouse|touchscreen|unknown default|unknown";

        $props["_obsolete"] = "bool";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function updateFormFields()
    {
        parent::updateFormFields();

        $this->_view = "$this->browser_name $this->browser_version / $this->platform_name $this->platform_version";

        $this->isObsolete();
    }

    /**
     * Tells if the browser is obsolete
     *
     * @return bool
     */
    function isObsolete()
    {
        $this->_badly_detected = $this->browser_version === "0.0";

        if (!$this->_badly_detected && isset(self::$supported_browsers[$this->browser_name])) {
            $min_version = self::$supported_browsers[$this->browser_name];

            $this->_obsolete = $min_version > $this->browser_version;
        }

        return $this->_obsolete;
    }

    /**
     * Get code name
     *
     * @return string
     */
    function getCodeName()
    {
        return CValue::read(self::$browser_code_names, $this->browser_name, CMbString::lower($this->browser_name));
    }

    /**
     * Get major version number
     *
     * @return int
     */
    function getMajorVersion()
    {
        return (int)$this->browser_version;
    }

    /**
     * @return CUserAuthentication[]
     */
    function loadRefUserAuthentication()
    {
        return $this->_ref_user_authentications = $this->loadBackRefs("user_authentications");
    }

    /**
     * User agent detection
     *
     * @param string|null $ua_string UA string
     *
     *
     * @throws Exception
     */
    public static function detect($ua_string = null)
    {
        try {
            $browscap = BrowscapFactory::create();
            $browser  = $browscap->getBrowser($ua_string);
        } catch (Exception $e) {
            CApp::log($e->getMessage(), CLogger::CHANNEL_ERROR);
            $browser = false;
        }

        return $browser;
    }


    /**
     * Create a User agent entry from a US string
     *
     * @param bool $store Store the new UA object
     *
     * @return self
     */
    static function create($store = true)
    {
        $user_agent = new self();

        $ua_string = CValue::read($_SERVER, "HTTP_USER_AGENT");
        if (!$ua_string) {
            return $user_agent;
        }

        if (!$user_agent->isInstalled()) {
            return $user_agent;
        }

        $user_agent->user_agent_string = substr($ua_string, 0, 255);

        if (!$user_agent->loadMatchingObjectEsc()) {
            if ($browser = self::detect($ua_string)) {
                $user_agent->browser_name    = $browser->browser;
                $user_agent->browser_version = $browser->version;

                $user_agent->platform_name    = $browser->platform;
                $user_agent->platform_version = $browser->platform_version;

                $user_agent->device_name     = $browser->device_name;
                $user_agent->device_maker    = $browser->device_maker;
                $user_agent->pointing_method = $browser->device_pointing_method;

                $user_agent->device_type = self::mapDeviceType($browser->device_type);
            }

            if ($store) {
                $user_agent->store();
            }
        }

        return $user_agent;
    }

    static function mapDeviceType($device_type)
    {
        $device_map = [
            "Mobile Device" => "mobile",
            "Mobile Phone"  => "mobile",
            "Desktop"       => "desktop",
            "Tablet"        => "tablet",
        ];

        return CValue::read($device_map, $device_type, "unknown");
    }
}
