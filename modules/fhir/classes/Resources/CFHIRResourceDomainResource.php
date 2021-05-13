<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use Ox\Core\CMbObject;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeNarrative;

/**
 * A domain resource
 */
class CFHIRResourceDomainResource extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = 'DomainResource';

    /** @var CFHIRDataTypeNarrative */
    public $text;

    /** @var CFHIRResource */
    public $contained;

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        parent::mapFrom($object);
        /* $this->text = CFHIRDataTypeNarrative::build(
           array(
             "status" => new CFHIRDataTypeCode("generated"),
             "div"    => new CFHIRDataTypeXhtml($object->_view),
           )
         );*/
    }
}

