<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Datatypes\Complex;

use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;

/**
 * FHIR data type
 */
class CFHIRDataTypeIdentifier extends CFHIRDataTypeComplex
{
    /** @var CFHIRDataTypeCode */
    public $use;

    /** @var CFHIRDataTypeCodeableConcept */
    public $type;

    /** @var CFHIRDataTypeUri */
    public $system;

    /** @var CFHIRDataTypeString */
    public $value;

    /** @var CFHIRDataTypePeriod */
    public $period;

    /** @var CFHIRDataTypeReference */
    public $assigner;
}
