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
 * Class CPDQm
 * Patient Demographics Query for Mobile
 */
class CPDQm extends CIHE
{
    /**
     * @var array
     */
    public static $transaction_iti78 = [
        "create",
        "search",
        "history",
        "read",
        "update",
        "delete",
    ];

    /**
     * @var array
     */
    public static $evenements = [
        // ITI-78
        "create"  => "CFHIRInteractionCreate",
        "update"  => "CFHIRInteractionUpdate",
        "delete"  => "CFHIRInteractionDelete",
        "read"    => "CFHIRInteractionRead",
        "search"  => "CFHIRInteractionSearch",
        "history" => "CFHIRInteractionHistory",
    ];

    /**
     * Construct
     *
     * @return CPDQm
     */
    public function __construct()
    {
        $this->domain = "ITI";
        $this->type   = "PDQm";

        $this->_categories = [
            "ITI-78" => self::$transaction_iti78,
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
