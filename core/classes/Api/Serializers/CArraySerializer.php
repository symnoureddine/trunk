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
 * Class CArraySerializer
 */
class CArraySerializer extends CAbstractSerializer
{

    /**
     * @inheritDoc
     */
    public function serialize(): array
    {
        return [
            'datas' => ($this->resource instanceof CItem) ? ($this->resource->transform(
            ))['datas'] : $this->resource->transform(),
            'metas' => $this->resource->getMetas(),
            'links' => $this->resource->getLinks(),
        ];
    }
}
