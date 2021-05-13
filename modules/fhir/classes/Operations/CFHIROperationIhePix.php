<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Operations;

use Ox\Interop\Eai\CDomain;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackbone;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Interactions\CFHIRInteraction;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceParameters;
use Ox\Interop\Fhir\Response\CFHIRResponse;
use Ox\Mediboard\Patients\CPatient;

/**
 * Description
 */
class CFHIROperationIhePix extends CFHIRInteraction
{
    public $name = "\$ihe-pix";

    /**
     * Get the resource method name
     *
     * @return string
     */
    public function getResourceMethodName(): string
    {
        $interaction = substr($this->name, 1);
        $interaction = preg_replace('/[^\w]/', '_', $interaction);

        return "operation_$interaction";
    }

    /**
     * @inheritdoc
     *
     * @param CPatient $result
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        $root = new CFHIRResourceParameters();

        if ($result) {
            $_res = CFHIR::makeResource($resource->getResourceType());
            $_res->mapFrom($result);

            $domains = CDomain::loadDomainIdentifiers($result);

            foreach ($domains as $_domain) {
                if (empty($result->_returned_oids) || in_array($_domain->OID, $result->_returned_oids)) {
                    $_bb       = new CFHIRDataTypeBackbone();
                    $_bb->name = "targetIdentifier";

                    $_bb->valueIdentifier = CFHIRDataTypeIdentifier::build(
                        [
                            "use"    => "official",
                            "system" => "urn:oid:$_domain->OID",
                            "value"  => $_domain->_identifier->id400,
                        ]
                    );

                    $root->parameter[] = $_bb;
                }
            }

            $patient                 = new CFHIRDataTypeBackbone();
            $patient->name           = "targetId";
            $patient->valueReference = CFHIRDataTypeReference::build(
                [
                    "reference" => $_res->getResourceType() . "/$result->patient_id",
                ]
            );

            $root->parameter[] = $patient;
        }

        return new CFHIRResponse($root);
    }
}
