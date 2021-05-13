<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes;

use DateTime;
use DOMElement;
use Ox\Interop\Fhir\CFHIR;

/**
 * FHIR data type
 */
class CFHIRDataTypeInstant extends CFHIRDataType
{
    /** @var DateTime */
    protected $value;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return CFHIR::getTimeUtc($this->_value, false);
    }

    /**
     * @inheritdoc
     */
    public function toJSON()
    {
        return CFHIR::getTimeUtc($this->_value, false);
    }

    /**
     * @inheritdoc
     */
    public function toXML(DOMElement $DOMElement)
    {
        return CFHIR::getTimeUtc($this->_value, false);
    }

    /**
     * @inheritdoc
     */
    public function fromJSON($value)
    {
        $this->_value = DateTime::createFromFormat(DateTime::ISO8601, $value);
    }

    /**
     * @inheritdoc
     */
    public function fromXML($value)
    {
        $this->_value = DateTime::createFromFormat(DateTime::ISO8601, $value);
    }
}
