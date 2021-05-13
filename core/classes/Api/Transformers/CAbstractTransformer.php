<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Transformers;

use Ox\Core\Api\Resources\CItem;

/**
 * Class CAbstractTransformer
 * Transform resource item to data
 */
abstract class CAbstractTransformer
{
    /** @var int */
    public const RECURSION_LIMIT = 1; // start at 0

    /** @var CItem $item */
    protected $item;

    /** @var string */
    protected $type;

    /** @var mixed */
    protected $id;

    /** @var array */
    protected $attributes = [];

    /** @var array */
    protected $links = [];

    /** @var array */
    protected $relationships = [];

    /**
     * CAbstractTransformer constructor.
     *
     * @param CItem $item
     */
    public function __construct(CItem $item)
    {
        $this->item = $item;
    }

    /**
     * @return array
     */
    abstract public function createDatas(): array;

    /**
     * @return array
     */
    public function render(): array
    {
        $datas_transformed = [];

        // datas
        $datas_transformed['datas'] = array_merge(
            [
                '_type' => $this->type, // underscore preserve collision with attributes
                '_id'   => $this->id,
            ],
            $this->attributes
        );

        // links
        if (!empty($this->links)) {
            $datas_transformed['links'] = $this->links;
        }

        // relationships
        if (!empty($this->relationships)) {
            $datas_transformed['relationships'] = $this->relationships;
        }

        return $datas_transformed;
    }


    /**
     * Necessary for json:api spec
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function createIdFromData($data): string
    {
        return md5(serialize($data));
    }

}
