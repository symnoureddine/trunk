<?php
/**
 * @package Mediboard\core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Serializers;

use Ox\Core\Api\Resources\CItem;

/**
 * Class CAbstractSerializer
 */
class CJsonApiSerializer extends CAbstractSerializer
{

    /** @var array */
    private $included = [];

    /**
     * @inheritDoc
     */
    public function serialize(): array
    {
        $resource = $this->resource;

        // Transform item to data
        $datas_transformed = $resource->transform();

        // Convert to json:api specifications
        if ($this->resource instanceof CItem) {
            $datas_converted = $this->convertToJsonApi($datas_transformed);
            $datas_optimized = $this->optimizeRelationships($datas_converted);
        } else {
            $datas_converted = [];
            foreach ($datas_transformed as $key => $datas_to_convert) {
                $datas_converted[] = $this->convertToJsonApi($datas_to_convert);
            }

            $datas_optimized = [];
            foreach ($datas_converted as $key => $datas_to_optimize) {
                $datas_optimized[] = $this->optimizeRelationships($datas_to_optimize);
            }
        }

        // Final document
        $document = [];

        // Data (must)
        $document['data'] = $datas_optimized;

        // Meta (must)
        $document['meta'] = $resource->getMetas();

        // Links (may)
        if (!empty($resource->getLinks())) {
            $document['links'] = $resource->getLinks();
        }

        // Includes (may)
        if (!empty($this->included)) {
            $document['included'] = array_values($this->included);
        }

        return $document;
    }

    /**
     * Convert datas transformed to json:api
     *
     * @param array $datas_to_convert
     *
     * @return array
     */
    private function convertToJsonApi($datas_to_convert): array
    {
        $data_converted = [];

        // type
        $data_converted['type'] = $datas_to_convert['datas']['_type'];
        unset($datas_to_convert['datas']['_type']);

        // id
        $data_converted['id'] = $datas_to_convert['datas']['_id'];
        unset($datas_to_convert['datas']['_id']);

        // attributes
        $data_converted['attributes'] = $datas_to_convert['datas'];

        // relationships
        if (array_key_exists('relationships', $datas_to_convert)) {
            $data_converted['relationships'] = [];
            foreach ($datas_to_convert['relationships'] as $relation) {
                // recursivly convert
                $data_converted['relationships'][] = $this->convertToJsonApi($relation);
            }
        }

        // links
        if (array_key_exists('links', $datas_to_convert)) {
            $data_converted['links'] = $datas_to_convert['links'];
        }

        return $data_converted;
    }


    /**
     * Optimize relations datas and create included datas
     *
     * @param array $datas_to_optimize
     *
     * @return array data optimized
     */
    private function optimizeRelationships(array $datas_to_optimize): array
    {
        if (!array_key_exists('relationships', $datas_to_optimize)) {
            return $datas_to_optimize;
        }

        // Reasign datas
        $datas_optimized                  = $datas_to_optimize;
        $datas_optimized['relationships'] = [];

        foreach ($datas_to_optimize['relationships'] as $relation) {
            $relation_type = $relation['type'];
            $relation_id   = $relation['id'];

            // Optimize
            if (!array_key_exists($relation_type, $datas_optimized['relationships'])) {
                // One relation ['relationships']['patients']['data']['type']
                $datas_optimized['relationships'][$relation_type] = [
                    'data' => [
                        'type' => $relation_type,
                        'id'   => $relation_id,
                    ],
                ];
            } else {
                // Many relations ['relationships']['patients']['data'][0]
                if (array_key_exists('type', $datas_optimized['relationships'][$relation_type]['data'])) {
                    // alter first iteration once a time
                    $first_relation_of_type = $datas_optimized['relationships'][$relation_type]['data'];
                    unset($datas_optimized['relationships'][$relation_type]['data']);
                    $datas_optimized['relationships'][$relation_type]['data'][] = $first_relation_of_type;
                }
                $datas_optimized['relationships'][$relation_type]['data'][] = [
                    'type' => $relation_type,
                    'id'   => $relation_id,
                ];
            }

            // Create includes and remove N3 relationships attributes
            $key_include      = $relation_type . '_' . $relation_id;
            $datas_to_include = $relation;
            if (array_key_exists('relationships', $datas_to_include)) {
                foreach ($datas_to_include['relationships'] as $sub_relation_key => $sub_relation) {
                    $datas_to_include['relationships'][$sub_relation_key] = [
                        'type'  => $sub_relation['type'],
                        'id'    => $sub_relation['id'],
                        'links' => $sub_relation['links'] ?? null,
                    ];
                }
            }
            $this->included[$key_include] = $datas_to_include;
        }

        return $datas_optimized;
    }

}
