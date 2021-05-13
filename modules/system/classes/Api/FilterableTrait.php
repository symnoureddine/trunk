<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Api;

use Ox\Core\Api\Request\CRequestApi;

/**
 * Description
 */
trait FilterableTrait
{
    /** @var SimpleFilter */
    private $filter;

    private function applyFilter(CRequestApi $request_api, array $array, bool $filter_values = true): array
    {
        if ($this->filter === null) {
            $this->filter = new SimpleFilter($request_api, $filter_values);
        }

        if ($this->filter->isEnabled()) {
            $array = $this->filter->apply($array);
        }

        return $array;
    }

    private function isFilterEnabled(): bool
    {
        return ($this->filter && $this->filter->isEnabled());
    }
}
