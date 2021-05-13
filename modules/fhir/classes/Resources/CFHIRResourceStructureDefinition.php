<?php

/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CStoredObject;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackbone;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;

/**
 * FIHR patient resource
 */
class CFHIRResourceStructureDefinition extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = "StructureDefinition";

    /** @var CFHIRDataTypeBackbone */
    public $parameter = array();
}
