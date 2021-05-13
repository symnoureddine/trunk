<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CMbException;
use ReflectionClass;

/**
 * Class CInteropNorm
 * Interoperability Norme
 */
abstract class CInteropNorm implements IShortNameAutoloadable
{
    /** @var string */
    public $name;

    /** @var string */
    public $domain;

    /** @var string */
    public $type;

    /** @var array */
    public static $object_handlers = [];

    /** @var array */
    public static $versions = [];

    /** @var array */
    public static $evenements = [];

    /** @var array */
    public $_categories = [];

    /**
     * Construct
     *
     * @return CInteropNorm
     */
    public function __construct()
    {
    }

    /**
     * Retrieve handlers list
     *
     * @return array Handlers list
     */
    public static function getObjectHandlers(): ?array
    {
        return self::$object_handlers;
    }

    /**
     * Retrieve events list of data format
     *
     * @return array Events list
     */
    public function getEvenements(): ?array
    {
        return self::$evenements;
    }

    /**
     * Retrieve versions list of data format
     *
     * @return array Versions list
     */
    public function getVersions(): ?array
    {
        return self::$versions;
    }

    /**
     * Retrieve document elements
     *
     * @return array
     */
    public function getDocumentElements(): ?array
    {
        return [];
    }

    /**
     * Retrieve transaction name
     *
     * @param string $code Event code
     *
     * @return string Transaction name
     */
    public static function getTransaction(string $code): ?string
    {
    }

    /**
     * Return data format object
     *
     * @param CExchangeDataFormat $exchange Instance of exchange
     *
     * @return object An instance of data format
     * @throws CMbException
     *
     */
    public static function getEvent(CExchangeDataFormat $exchange)
    {
    }

    /**
     * Get tag
     *
     * @param int $group_id group id
     *
     * @return string|null
     */
    public static function getTag(?int $group_id = null): ?string
    {
    }

    /**
     * Retrieve profil
     *
     * @return string
     */
    public function getDomain(): ?string
    {
        return $this->domain ? $this->domain : "none";
    }

    /**
     * Retrieve type
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type ? $this->type : "none";
    }

    /**
     * Retrieve events
     *
     * @return array Events list
     */
    public function getEvents(): ?array
    {
        $events = $this->getEvenements();

        $temp = [];
        foreach ($this->_categories as $_transaction => $_events) {
            foreach ($_events as $_event_name) {
                if (array_key_exists($_event_name, $events)) {
                    $temp[$_transaction][$_event_name] = $events[$_event_name];
                }
            }
        }

        if (empty($temp)) {
            $temp["none"] = $events;
        }

        return $temp;
    }

    /**
     * Get objects
     *
     * @return array CInteropNorm collection
     */
    public static function getObjects(): ?array
    {
        $standards = [];
        foreach (CApp::getChildClasses(CInteropNorm::class, false, true) as $_interop_norm) {
            /* We check if the class is instantiable */
            $reflection = new ReflectionClass($_interop_norm);
            if (!$reflection->isInstantiable()) {
                continue;
            }

            /** @var CInteropNorm $norm */
            $norm = new $_interop_norm();

            if (!$norm->name || !$norm->type) {
                continue;
            }

            $domain_name = $norm->getDomain();
            $type        = $norm->getType();
            $events      = $norm->getEvents();

            $standards[$norm->name][$domain_name][$type] = $events;
        }

        return $standards;
    }
}
