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
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUnsignedInt;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeComplex;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundle;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundleEntry;
use Ox\Interop\Fhir\Response\CFHIRResponse;

/**
 * This interaction searches a set of resources based on some filter criteria
 */
class CFHIRInteractionSearch extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Search";

    /** @var string Resource ID */
    public $resource_id;

    /**
     * @inheritdoc
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        $bundle = new CFHIRResourceBundle();
        if (!$result) {
            return new CFHIRResponse($bundle);
        }

        $bundle->total = new CFHIRDataTypeUnsignedInt($result["total"]);
        $bundle->type  = "searchset";

        $parts = explode("?", urldecode(CMbArray::get($_SERVER, "REQUEST_URI")), 2);

        $params = null;
        if (count($parts) > 1) {
            $params = $parts[1];
        }

        $root = CFHIRController::getUrl("fhir_search", ['resource' => $resource->getResourceType()]);

        $url           = $root . ($params ? "?$params" : "");
        $parsed_params = CFHIR::parseQueryString($params, true);

        // relation = self
        $link           = new CFHIRDataTypeComplex();
        $link->relation = "self";
        $link->url      = urlencode($url);
        $bundle->link[] = $link;

        if ($result["paginate"]) {
            // relation = next
            if ($result["offset"] + $result["step"] < $result["total"]) {
                $next_params            = $parsed_params;
                $next_params["_offset"] = [$result["offset"] + $result["step"]];
                $link                   = new CFHIRDataTypeComplex();
                $link->relation         = "next";
                $link->url              = $root . "?" . CFHIR::makeQueryString($next_params);
                $bundle->link[]         = $link;
            }

            if ($result["offset"]) {
                // relation = previous
                $prev_params            = $parsed_params;
                $prev_params["_offset"] = [$result["offset"] - $result["step"]];

                $link           = new CFHIRDataTypeComplex();
                $link->relation = "previous";
                $link->url      = $root . "?" . CFHIR::makeQueryString($prev_params);
                $bundle->link[] = $link;

                // relation = first
                $prev_params = $parsed_params;
                unset($prev_params["_offset"]);

                $link           = new CFHIRDataTypeComplex();
                $link->relation = "first";
                $link->url      = $root . "?" . CFHIR::makeQueryString($prev_params);
                $bundle->link[] = $link;
            }
        }

        foreach ($result["list"] as $_resource_data) {
            /** @var CMbObject $_resource_data */
            $_res            = CFHIR::makeResource($resource->getResourceType());
            $_res->_sender   = $resource->_sender;
            $_res->_receiver = $resource->_receiver;
            $_res->mapFrom($_resource_data);

            $_entry           = new CFHIRResourceBundleEntry();
            $_entry->resource = $_res;

            $_entry->fullUrl = CFHIRController::getUrl(
                "fhir_read",
                [
                    'resource'    => $_res->getResourceType(),
                    'resource_id' => $_res->getResourceId(),
                ]
            );

            $bundle->entry[] = $_entry;
        }

        return new CFHIRResponse($bundle);
    }

    /**
     * Get query name
     *
     * @return mixed
     */
    public function getQueryName(): ?string
    {
        return $this->resource_id;
    }
}
