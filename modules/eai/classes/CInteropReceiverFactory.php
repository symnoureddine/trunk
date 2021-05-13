<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CClassMap;
use Ox\Interop\Dmp\CReceiverDMP;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Hl7\CReceiverHL7v2;
use Ox\Interop\Hl7\CReceiverHL7v3;
use Ox\Interop\Hprimxml\CDestinataireHprim;

/**
 * Class CInteropReceiver
 * Interoperability Receiver
 */
class CInteropReceiverFactory implements IShortNameAutoloadable
{
    /**
     * Get DMP receiver
     *
     * @return CReceiverDMP|CInteropReceiver
     * @throws CEAIException
     */
    public static function makeDMP()
    {
        return self::make(CReceiverDMP::class);
    }

    /**
     * Get receiver instance
     *
     * @param string $parent
     * @param string $receiver_type
     *
     * @return CInteropReceiver
     * @throws CEAIException
     */
    private static function make($parent, $receiver_type = null)
    {
        $childrens = CClassMap::getInstance()->getClassChildren(CInteropReceiver::class);
        if (!in_array($parent, $childrens)) {
            throw new CReceiverException('CInteropReceiverFactory-msg-Class missing', $parent);
        }

        if ($receiver_type === null) {
            return new $parent();
        }

        $parent_sn = CClassMap::getSN($parent);
        foreach ($childrens as $_child) {
            $short_name = CClassMap::getSN($_child);
            if ($short_name === $parent_sn . $receiver_type) {
                return new $_child();
            }
        }

        throw new CReceiverException('CInteropReceiverFactory-msg-Class missing', $parent_sn . $receiver_type);
    }

    /**
     * Get HL7v2 receiver
     *
     * @param string|null $receiver_type Receiver type
     *
     * @return CReceiverHL7v2|CInteropReceiver
     * @throws CEAIException
     */
    public static function makeHL7v2($receiver_type = null)
    {
        return self::make(CReceiverHL7v2::class, $receiver_type);
    }

    /**
     * Get HL7v3 receiver
     *
     * @param string|null $receiver_type Receiver type
     *
     * @return CReceiverHL7v3|CInteropReceiver
     * @throws CEAIException
     */
    public static function makeHL7v3($receiver_type = null)
    {
        return self::make(CReceiverHL7v3::class, $receiver_type);
    }

    /**
     * Get FHIR receiver
     *
     * @return CReceiverFHIR|CInteropReceiver
     * @throws CEAIException
     */
    public static function makeFHIR()
    {
        return self::make(CReceiverFHIR::class);
    }

    /**
     * Get H'XML receiver
     *
     * @param string|null $receiver_type Receiver type
     *
     * @return CDestinataireHprim|CInteropReceiver
     * @throws CEAIException
     */
    public static function makeHprimXML($receiver_type = null)
    {
        return self::make(CDestinataireHprim::class, $receiver_type);
    }
}
