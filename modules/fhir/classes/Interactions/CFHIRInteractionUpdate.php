<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Request\CFHIRRequest;
use Ox\Interop\Fhir\Resources\CFHIRResource;

/**
 * The update interaction creates a new current version for an existing resource or creates an initial version if no
 * resource already exists for the given id
 */
class CFHIRInteractionUpdate extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Update";

    /**
     * Generate resource
     *
     * @param CMbObject $object object
     *
     * @return CFHIRRequest
     */
    public function build(CMbObject $object): CFHIRRequest
    {
        $resource   = CFHIR::makeResource($this->resourceType);
        $class_name = CFHIREvent::getEventClass($resource);

        /** @var CFHIREvent $event */
        $event = new $class_name();
        $event->_receiver = $this->_receiver;

        $bundle = $event->build($object);

        return new CFHIRRequest($bundle);
    }

    /**
     * Build the query
     *
     * @param array $data data
     *
     * @return array
     */
    public function buildQuery(?array $data = array()): array
    {
        $id = null;

        foreach ($this->parameters as $_param) {
            if ($_param["field"] === "_id") {
                $id = urlencode($_param["value"]);
            }
        }

        return [
            // TODO XDS TOOLKIT : Si on utilise pas XDS Toolkit, il faut un event sinon non
            "event" => $this->resourceType . "/" . $id,
            "data"  => CMbArray::get($data, 0),
        ];
    }
}
