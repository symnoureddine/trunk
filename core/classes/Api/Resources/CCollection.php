<?php
/**
 * @package Mediboard\\Api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Resources;

use Countable;
use Iterator;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestLimit;

/**
 * Class CCollection
 */
class CCollection extends CAbstractResource implements Iterator, Countable
{

    /** @var CItem[] */
    private $items = [];

    /** @var int */
    private $position = 0;

    /**
     * CCollection constructor.
     *
     * @param array $datas
     *
     * @throws CApiException
     */
    public function __construct(array $datas)
    {
        $model_class = null;
        if (!empty($datas)) {
            $first_element = $datas[array_key_first($datas)];
            $model_class   = is_object($first_element) ? get_class($first_element) : null;
        }

        parent::__construct(CAbstractResource::TYPE_COLLECTION, $datas, $model_class);

        $this->createItems();
    }

    /**
     * @return void
     * @throws CApiException
     */
    private function createItems(): void
    {
        foreach ($this->datas as $key => $data) {
            $this->items[] = new CItem($data);
        }
    }

    /**
     * @inheritDoc
     * @throws CApiException
     */
    public function transform(): array
    {
        $datas_transformed = [];
        foreach ($this->items as $key => $item) {
            $item                = $this->propageSettings($item);
            $datas_transformed[] = $item->transform();
        }

        return $datas_transformed;
    }

    /**
     * @param CAbstractResource $item
     *
     * @return CAbstractResource $item
     * @throws CApiException
     */
    private function propageSettings(CAbstractResource $item): CAbstractResource
    {
        if ($this->name) {
            $item->setName($this->name);
        }
        if ($this->model_fieldsets) {
            $model_fieldsets = [];
            foreach ($this->model_fieldsets as $relation => $model_fieldset) {
                foreach ($model_fieldset as $fieldset) {
                    $model_fieldsets[] = $relation === self::CURRENT_RESOURCE_NAME ? "$fieldset" : "$relation.$fieldset";
                }
            }

            $item->setModelFieldsets($model_fieldsets);
        }
        if ($this->model_relations) {
            $item->setModelRelations($this->model_relations);
        }

        return $item;
    }


    /**
     * @param int      $offset
     * @param int      $limit
     * @param int|null $total
     *
     * @return CCollection
     */
    public function createLinksPagination(int $offset, int $limit, int $total = null): CCollection
    {
        $links = [];

        // self
        $links['self'] = $this->createLinkUrl($offset, $limit);

        // next
        $next          = $offset + $limit;
        $links['next'] = $this->createLinkUrl($next, $limit);

        // prev
        $prev = $offset - $limit;
        if ($prev >= 1) {
            $links['prev'] = $this->createLinkUrl($prev, $limit);
        }

        // first
        $links['first'] = $this->createLinkUrl(CRequestLimit::OFFSET_DEFAULT, $limit);

        // last
        if ($total) {
            if ($next + $limit > $total) {
                unset($links['next']);
            }

            $modulus = $total % $limit;

            $links['last'] = $this->createLinkUrl($total - $modulus, $limit);
        }

        $this->addLinks($links);

        return $this;
    }

    /**
     * @param int $offset
     *
     * @param int $limit
     *
     * @return string
     */
    private function createLinkUrl($offset, $limit): string
    {
        if ($query = parse_url($this->request_url, PHP_URL_QUERY)) {
            $params = explode('&', urldecode($query));

            foreach ($params as $key => $_param) {
                if (strpos($_param, 'offset=') === 0 || strpos($_param, 'limit=') === 0) {
                    unset($params[$key]);
                }
            }

            $params[] = 'offset=' . $offset;
            $params[] = 'limit=' . $limit;

            // Compat with symfony normalized query string
            sort($params);

            return str_replace($query, implode('&', $params), $this->request_url);
        }

        return $this->request_url .= '?offset=' . $offset . '&limit=' . $limit;
    }

    /**
     * @return void
     */
    protected function setDefaultMetas(): void
    {
        parent::setDefaultMetas();
        $this->addMeta('count', count($this->datas));
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->position = 0;
        $this->items    = array_values($this->items);
    }

    /**
     * @return CItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

}
