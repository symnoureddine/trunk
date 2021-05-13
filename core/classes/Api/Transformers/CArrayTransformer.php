<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Transformers;

/**
 * Class CArrayTransformer
 */
class CArrayTransformer extends CAbstractTransformer
{

    /**
     *
     * @return array
     */
    public function createDatas(): array
    {
        $datas      = $this->item->getDatas();
        $this->type = $this->item->getName() ?? 'undefined';

        // Id
        if (array_key_exists('id', $datas)) {
            $this->id = $datas['id'];
            unset($datas['id']);
        } else {
            $this->id = $this->createIdFromData($datas);
        }

        // Attributes
        foreach ($datas as $key => $sub_datas) {
            // todo filter
            $this->attributes[$key] = $sub_datas;
        }

        return $this->render();
    }

}
