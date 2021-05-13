<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Eai;

/**
 * Represent item in the report
 */
class CItemReport
{

    /** @var string */
    private $severity;
    /** @var string */
    private $data;

    /** @var CItemReport[] */
    private $sub_items = [];

    public const SEVERITY_ERROR   = 1;
    public const SEVERITY_WARNING = 2;
    public const SEVERITY_SUCCESS = 3;

    public function __construct(string $data, int $severity)
    {
        $this->severity = $severity;
        $this->data     = $data;
    }

    /**
     * Get data
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Get severity
     * @return int
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * @param CItemReport $item
     *
     * @return $this
     */
    public function addSubItem(CItemReport $item): self
    {
        $this->sub_items[] = $item;

        return $this;
    }

    /**
     * @param string $data
     * @param int    $severity
     *
     * @return $this
     */
    public function addSubData(string $data, int $severity): self
    {
        $this->sub_items[] = new CItemReport($data, $severity);

        return $this;
    }

    /**
     * @return CItemReport[]
     */
    public function getSubItems(): array
    {
        return $this->sub_items;
    }
}
