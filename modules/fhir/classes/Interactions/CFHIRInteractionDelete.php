<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

/**
 * The delete interaction removes an existing resource
 */
class CFHIRInteractionDelete extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Delete";

    /**
     * Build the query
     *
     * @return array
     */
    public function buildQuery(?array $data = []): array
    {
        $id = $version_id = null;

        $params = [];

        foreach ($this->parameters as $_param) {
            if ($_param["field"] === "_id") {
                $id = urlencode($_param["value"]);
            } else {
                $params[] = $_param["field"] . "=" . urlencode($_param["value"]);
            }
        }

        $params[] = "_format=" . urlencode($this->format);

        return [
            "event" => $this->resourceType . "/" . $id,
            "data"  => implode("&", $params),
        ];
    }
}
