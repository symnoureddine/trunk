<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Transformers;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Resources\CAbstractResource;
use Ox\Core\Api\Resources\CItem;
use Ox\Core\CMbFieldSpec;
use Ox\Core\CModelObject;

/**
 * Class CModelObjectTransformer
 */
class CModelObjectTransformer extends CAbstractTransformer
{
    /**
     * @return array
     * @throws CApiException
     */
    public function createDatas(): array
    {
        /** @var CModelObject $model */
        $model       = $this->item->getDatas();
        $this->type  = $this->item->getName() ?? $model::RESOURCE_NAME;
        $this->id    = $model->_id;
        $this->links = [
            'self'   => $model->getApiLink(),
            'schema' => $model->getApiSchemaLink($this->item->getFieldsetsByRelation()),
        ];

        if ($history_link = $model->getApiHistoryLink()) {
            $this->links['history'] = $history_link;
        }

        // Default fieldset for item
        if ($this->item->getFieldsetsByRelation() === null) {
            $this->item->addModelFieldset([$model::FIELDSET_DEFAULT]);
        }

        $mapping = $model->getFieldsSpecsByFieldsets($this->item->getFieldsetsByRelation());
        foreach ($mapping as $field_name => $spec) {
            // Access data
            $field_value = $model->$field_name === '' ? null : $model->$field_name;

            /** @var CMbFieldSpec $spec */
            if ($field_value !== null && ($spec->getPHPSpec() !== $spec::PHP_TYPE_STRING)) {
                // Force data type
                settype($field_value, $spec->getPHPSpec());
            }

            $this->attributes[$field_name] = $field_value;
        }

        // Relationships
        if ($this->item->getRecursionDepth() >= self::RECURSION_LIMIT) {
            return $this->render();
        }

        // Default relations
        if ($this->item->getModelRelations() === null) {
            $this->item->setModelRelations($model::RELATIONS_DEFAULT);
        }

        foreach ($this->item->getModelRelations() as $relation_name) {
            // Naming convention
            $method_name = 'getResource' . ucfirst($relation_name);

            if (!method_exists($model, $method_name)) {
                throw new CApiException("Invalid method name '{$method_name}' in class '{$model->_class}'");
            }

            /** @var CAbstractResource $resource */
            $resource = $model->$method_name();
            if ($resource === null) {
                continue;
            }

            if (!$resource instanceof CAbstractResource) {
                throw new CApiException("Invalid resource returned in class '{$model->_class}::{$method_name}'");
            }

            // Set recursion depth limit
            $resource->setRecursionDepth($this->item->getRecursionDepth() + 1);

            // Set fieldsets on relations
            if ($resource->isModelObjectResource()) {
                $resource->setModelFieldsets($this->item->getFieldsetsByRelation($relation_name) ?? []);
            }

            if ($resource instanceof CItem) {
                // Item
                $this->relationships[] = $resource->transform();
            } else {
                // Collection
                $relation_datas = $resource->transform();
                foreach ($relation_datas as $relation_data) {
                    $this->relationships[] = $relation_data;
                }
            }
        }

        return $this->render();
    }
}
