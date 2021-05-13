<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;
use Ox\Interop\Fhir\CFHIR;

/**
 * FHIR data type
 */
class CFHIRDataTypeContained extends CFHIRDataTypeComplex {
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function jsonSerialize()
    {
        $result = parent::jsonSerialize();
        return array_values($result)[0];
    }
}
