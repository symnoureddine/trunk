<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Transformers;

/**
 * Class CObjectTransformer
 */
class CObjectTransformer extends CAbstractTransformer
{
    /**
     *
     * @return array
     */
    public function createDatas(): array
    {
        $object = $this->item->getDatas();

        // id
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        } else {
            $this->id = $this->createIdFromData($object);
        }

        // type
        $this->type = $this->item->getName() ?? 'undefined';


        // Attributes
        foreach (get_object_vars($object) as $key => $sub_datas) {
            // todo filter
            $this->attributes[$key] = $sub_datas;
        }

        return $this->render();
    }
}
