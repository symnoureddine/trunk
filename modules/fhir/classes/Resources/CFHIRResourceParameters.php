<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackbone;

/**
 * FIHR patient resource
 */
class CFHIRResourceParameters extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = "Parameters";

    /** @var CFHIRDataTypeBackbone */
    public $parameter = [];
}
