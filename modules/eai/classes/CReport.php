<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

use Countable;
use DateTime;
use Iterator;
use Ox\Core\CMbArray;

/**
 * To allow to generate report after execute an action
 */
class CReport implements Countable, Iterator
{
    /** @var string */
    private $title;

    /** @var DateTime */
    private $created_datetime;

    /** @var CItemReport[] */
    private $items = [];

    /** @var int */
    private $position;

    public function __construct(string $title)
    {
        $this->position         = 0;
        $this->title            = $title;
        $this->created_datetime = new DateTime();
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Add item to report
     *
     * @param string $data
     * @param int    $severity
     *
     * @return CReport
     */
    public function addData(string $data, int $severity): self
    {
        $this->items[] = new CItemReport($data, $severity);

        return $this;
    }

    /**
     * @param CItemReport $item
     *
     * @return CReport
     */
    public function addItem(CItemReport $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add item to report
     *
     * @param CItemReport $item
     *
     * @return CItemReport[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Transforme report in JSON string
     *
     * @return string
     */
    public function toJson(): string
    {
        $result = [];
        foreach ($this->getItems() as $_item) {
            $item['data']     = utf8_encode($_item->getData());
            $item['severity'] = $_item->getSeverity();
            $result[] = $item;
        }

        return json_encode($result);
    }

    /**
     * Transform json report to a CReport
     *
     * @param string $json
     *
     * @return CReport
     */
    public static function toObject(string $json): CReport
    {
        $report = new CReport('Report DMP');

        foreach (json_decode($json) as $item) {
            $report->addData(utf8_decode($item->data), $item->severity);
        }

        return $report;
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
     * @inheritDoc
     */
    public function count()
    {
        return count($this->items);
    }
}
