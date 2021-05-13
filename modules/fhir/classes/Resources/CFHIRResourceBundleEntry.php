<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;

/**
 * FIHR patient resource
 */
class CFHIRResourceBundleEntry extends CFHIRResource
{
    /** @var string  */
    public const RESOURCE_TYPE = 'BundleEntry';
    /** @var bool  */
    protected const APPEND_SELF = false;

    /** @var CFHIRDataTypeUri */
    public $fullUrl;
    /** @var CFHIRResource */
    public $resource;
    /** @var CFHIRDataTypeBackboneElement */
    public $request;
    /** @var CFHIRDataTypeBackboneElement */
    public $response;
}
