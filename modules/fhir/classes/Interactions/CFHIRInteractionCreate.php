<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Request\CFHIRRequest;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundle;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundleEntry;
use Ox\Interop\Fhir\Response\CFHIRResponse;

/**
 * The create interaction creates a new resource in a server-assigned location
 */
class CFHIRInteractionCreate extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Create";

    /**
     * @inheritdoc
     */
    function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        $bundle       = new CFHIRResourceBundle();
        $bundle->type = "transaction-response";

        foreach ($result as $_result) {
            foreach ($_result as $resource_key => $_object) {
                $entry           = new CFHIRResourceBundleEntry();
                $entry->response = CFHIRDataTypeBackboneElement::build(
                    [
                        "status"       => $_object->_id ? "201" : "422",
                        "location"     => $_object->_id ? CFHIRController::getUrl(
                            "fhir_read",
                            [
                                'resource'    => $resource_key,
                                'resource_id' => $_object->_id,
                            ]
                        ) : null,
                        // Ajout du "t" dans le last-updated en response
                        "lastModified" => new CFHIRDataTypeInstant(CMbDT::datetime("now", false)),
                    ]
                );

                $bundle->entry[] = $entry;
            }
        }

        return new CFHIRResponse($bundle);
    }

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

        $resource = $event->build($object);

        return new CFHIRRequest($resource);
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
        return [
            // TODO XDS TOOLKIT : Si on utilise pas XDS Toolkit, il faut un event sinon non
            "event" => $this->resourceType,
            "data"  => CMbArray::get($data, 0),
        ];
    }
}
