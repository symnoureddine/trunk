<?php
/**
 * @package Mediboard\Ihe
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ihe;

use Ox\Interop\Eai\CExchangeDataFormat;

/**
 * Class CMHD
 * Patient Demographics Query for Mobile
 */
class CMHD extends CIHE
{
    /**
     * @var array
     */
    public static $transaction_iti65 = [
        "create",
    ];

    /**
     * @var array
     */
    public static $transaction_iti67 = [
        "read",
        "search",
    ];

    /**
     * @var array
     */
    public static $transaction_iti68 = [
        "read",
        "search",
    ];

    /**
     * @var array
     */
    public static $evenements = [
        // ITI-68
        "read"   => "CFHIRInteractionRead",
        "search" => "CFHIRInteractionSearch",
        "create" => "CFHIRInteractionCreate",
    ];

    /**
     * Construct
     *
     * @return string
     */
    public function __construct()
    {
        $this->domain = "ITI";
        $this->type   = "MHD";

        $this->_categories = [
            "ITI-65" => self::$transaction_iti65,
            "ITI-67" => self::$transaction_iti67,
            "ITI-68" => self::$transaction_iti68,
        ];

        parent::__construct();
    }

    /**
     * @see parent::getEvenements
     */
    public function getEvenements(): ?array
    {
        return self::$evenements;
    }

    /**
     * @see parent::getVersions
     */
    public function getVersions(): ?array
    {
        return self::$versions;
    }

    /**
     * Return data format object
     *
     * @param CExchangeDataFormat $exchange Instance of exchange
     *
     * @return object An instance of data format
     */
    public static function getEvent(CExchangeDataFormat $exchange)
    {
    }
}
