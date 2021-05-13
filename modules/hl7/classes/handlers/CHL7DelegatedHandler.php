<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\handlers;

use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Interop\Hl7\CReceiverHL7v2;

/**
 * Class CHL7DelegatedHandler
 * HL7 Object Handler
 */
class CHL7DelegatedHandler implements IShortNameAutoloadable
{
    /**
     * Is message supported ?
     *
     * @param string         $message  Message
     * @param string         $code     Code
     * @param CReceiverHL7v2 $receiver Recevier
     *
     * @return bool
     */
    public function isMessageSupported(string $message, string $code, CReceiverHL7v2 $receiver): bool
    {
        if (!$receiver->isMessageSupported("CHL7Event{$message}{$code}")) {
            return false;
        }

        return true;
    }

    /**
     * Send message
     *
     * @param string    $message  Message
     * @param string    $code     Code
     * @param CMbObject $mbObject Object
     *
     * @return null|bool|CHEvent|string
     *
     * @throws CMbException
     */
    public function sendEvent(string $message, string $code, CMbObject $mbObject)
    {
        /** @var CReceiverHL7v2 $receiver */
        $receiver = $mbObject->_receiver;

        if (!$code) {
            throw new CMbException("CITI-code-none");
        }
        $class = "CHL7v2Event" . $message . $code;

        if (!class_exists($class)) {
            trigger_error("class-CHL7v2Event" . $message . $code . "-not-found", E_USER_ERROR);
            return null;
        }

        $event = new $class();

        return $receiver->sendEvent($event, $mbObject);
    }
}
