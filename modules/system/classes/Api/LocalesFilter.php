<?php

/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System\Api;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\CMbString;

/**
 * Description
 */
class LocalesFilter
{
    public const SEARCH_MODE_STARTS_WITH = 'starts_with';
    public const SEARCH_MODE_ENDS_WITH   = 'ends_with';
    public const SEARCH_MODE_CONTAINS    = 'contains';
    public const SEARCH_MODE_EQUAL       = 'equal';

    public const SEARCH_IN_KEY   = 'key';
    public const SEARCH_IN_VALUE = 'value';

    public const SEARCH_MODES = [
        self::SEARCH_MODE_STARTS_WITH,
        self::SEARCH_MODE_ENDS_WITH,
        self::SEARCH_MODE_CONTAINS,
        self::SEARCH_MODE_EQUAL,
    ];

    public const SEARCH_IN = [
        self::SEARCH_IN_KEY,
        self::SEARCH_IN_VALUE,
    ];

    /** @var string */
    private $search;
    /** @var string */
    private $search_mode;
    /** @var string */
    private $search_in;

    public function __construct(CRequestApi $request)
    {
        $this->search      = $request->getRequest()->get('search');
        $this->search_mode = $request->getRequest()->get('search_mode', self::SEARCH_MODE_CONTAINS);
        $this->search_in   = $request->getRequest()->get('search_in', self::SEARCH_IN_KEY);

        if ($this->search_mode && !in_array($this->search_mode, self::SEARCH_MODES, true)) {
            throw new CApiException(
                'Search mode candidate \'' . $this->search_mode . '\' is not in ' . implode('|', self::SEARCH_MODES)
            );
        }

        if ($this->search_in && !in_array($this->search_in, self::SEARCH_IN, true)) {
            throw new CApiException(
                'Search in candidate \'' . $this->search_in . '\' is not in ' . implode('|', self::SEARCH_IN)
            );
        }
    }

    public function isEnabled(): bool
    {
        return (bool)$this->search;
    }

    public function apply(array $locales): array
    {
        $filtered_locales = [];
        foreach ($locales as $_key => $_value) {
            $haystack = ($this->search_in === self::SEARCH_IN_KEY) ? $_key : $_value;

            if ($this->search && !$this->isValidLocal($this->search, $haystack, $this->search_mode)) {
                continue;
            }

            $filtered_locales[$_key] = $_value;
        }

        return $filtered_locales;
    }

    private function isValidLocal(string $search_value, string $haystack, string $search_mode): bool
    {
        switch ($search_mode) {
            case self::SEARCH_MODE_CONTAINS:
                return (strpos($haystack, $search_value) !== false);

            case self::SEARCH_MODE_EQUAL:
                return ($search_value === $haystack);

            case self::SEARCH_MODE_STARTS_WITH:
                return (strpos($haystack, $search_value) === 0);

            case self::SEARCH_MODE_ENDS_WITH:
                return CMbString::endsWith($haystack, $search_value);

            default:
                throw new CApiException(
                    'Search mode candidate \'' . $search_mode . '\' is not in '
                    . implode('|', self::SEARCH_MODES)
                );
        }
    }
}
