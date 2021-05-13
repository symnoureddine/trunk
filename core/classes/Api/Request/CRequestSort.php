<?php

/**
 * @package Mediboard\Core\Fractal
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Request;

use Ox\Core\Api\Exceptions\CApiRequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CRequestSort
 */
class CRequestSort implements IRequestParameter
{
    /** @var string */
    public const SORT_ASC = 'asc';

    /** @var string */
    public const SORT_DESC = 'desc';

    /** @var string */
    public const QUERY_KEYWORD_SORT = 'sort';

    /** @var string */
    public const SORT_SEPARATOR = ',';

    /** @var array */
    private $fields;

    /**
     *
     * @param Request $request
     *
     * @throws CApiRequestException
     */
    public function __construct(Request $request)
    {
        $this->fields = $this->getFieldsFromRequest($request);
    }

    /**
     * @return array|null
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @param null $default
     *
     * @return string|null
     */
    public function getSqlOrderBy($default = null): ?string
    {
        if (empty($this->fields)) {
            return $default ?: null;
        }

        // todo check if num or fileds in specs
        $return = [];
        foreach ($this->fields as [$_field, $_type]) {
            $return[] = "{$_field} {$_type}";
        }

        return implode(',', $return);
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws CApiRequestException
     */
    private function getFieldsFromRequest(Request $request): array
    {
        $sort = $request->query->get(static::QUERY_KEYWORD_SORT, null);

        if ($sort === null || $sort === '') {
            return [];
        }

        $fields = [];

        foreach (explode(self::SORT_SEPARATOR, $sort) as $_field) {
            // Prevent SQL injection #1
            if (substr_count($_field, ' ')) {
                throw new CApiRequestException('Malformated sorting fields');
            }

            switch ($_field[0]) {
                case '-':
                    $_type  = static::SORT_DESC;
                    $_field = substr($_field, 1);
                    break;
                case '+':
                    $_type  = static::SORT_ASC;
                    $_field = substr($_field, 1);
                    break;
                default:
                    $_type = static::SORT_ASC;
                    break;
            }

            // Prevent SQL injection #2
            $fields[] = [addslashes($_field), $_type];
        }

        return $fields;
    }
}
