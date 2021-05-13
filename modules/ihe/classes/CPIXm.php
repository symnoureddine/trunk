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
 * Class CPIXm
 * Patient Demographics Query for Mobile
 */
class CPIXm extends CIHE
{
    /**
     * @var array
     */
    public static $transaction_iti83 = [
        "ihe-pix",
    ];

    /**
     * @var array
     */
    public static $evenements = [
        // ITI-83
        "ihe-pix" => "CFHIROperationIhePix",
    ];

    /**
     * Construct
     *
     * @return CPIXm
     */
    public function __construct()
    {
        $this->domain = "ITI";
        $this->type   = "PIXm";

        $this->_categories = [
            "ITI-83" => self::$transaction_iti83,
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
