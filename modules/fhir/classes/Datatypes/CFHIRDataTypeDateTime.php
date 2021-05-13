<?php

/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes;

use DOMElement;
use Exception;
use Ox\Core\CMbDT;
use Ox\Interop\Fhir\CFHIR;

/**
 * FHIR data type
 */
class CFHIRDataTypeDateTime extends CFHIRDataType
{
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function toJSON()
    {
        return CFHIR::getTimeUtc($this->_value, false);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function toXML(DOMElement $DOMElement)
    {
        return CFHIR::getTimeUtc($this->_value, false);
    }
}
