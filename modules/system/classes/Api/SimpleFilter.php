<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Api;

use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\CMbString;

/**
 * Description
 */
class SimpleFilter
{
    /** @var string */
    private $search;

    /** @var bool */
    private $filter_values = true;

    public function __construct(CRequestApi $request, bool $filter_values = true)
    {
        $this->search = $request->getRequest()->get('search');

        if ($this->search) {
            $this->search = CMbString::lower($this->search);
        }

        $this->filter_values = $filter_values;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->search;
    }

    public function apply(array $array): array
    {
        if (!$this->isEnabled()) {
            return $array;
        }

        $filtered_array = [];
        foreach ($array as $_key => $_value) {
            if (str_contains(CMbString::lower($this->filter_values ? $_value : $_key), $this->search)) {
                $filtered_array[$_key] = $_value;
            }
        }

        return $filtered_array;
    }
}
