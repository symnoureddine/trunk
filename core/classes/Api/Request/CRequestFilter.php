<?php
/**
 * @package Mediboard\Core\Request
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Request;

use Countable;
use Iterator;
use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\CSQLDataSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create filters from a Request object
 */
class CRequestFilter implements IRequestParameter, Iterator, Countable
{
    /** @var string */
    public const QUERY_KEYWORD_FILTER = 'filter';

    /** @var string */
    public const FILTER_PART_SEPARATOR = '.';

    public const FILTER_SEPARATOR = ',';

    // TODO Add FILTER_BETWEEN AND FILTER_NOT_BETWEEN
    /** @var string */
    public const FILTER_EQUAL = 'equal';

    /** @var string */
    public const FILTER_NOT_EQUAL = 'notEqual';

    /** @var string */
    public const FILTER_LESS = 'less';

    /** @var string */
    public const FILTER_LESS_OR_EQUAL = 'lessOrEqual';

    /** @var string */
    public const FILTER_GREATER = 'greater';

    /** @var string */
    public const FILTER_GREATER_OR_EQUAL = 'greaterOrEqual';

    /** @var string */
    public const FILTER_IN = 'in';

    /** @var string */
    public const FILTER_NOT_IN = 'notIn';

    /** @var string */
    public const FILTER_IS_NULL = 'isNull';

    /** @var string */
    public const FILTER_IS_NOT_NULL = 'isNotNull';

    /** @var string */
    public const FILTER_BEGIN_WITH = 'beginWith';

    /** @var string */
    public const FILTER_DO_NOT_BEGIN_WITH = 'doNotBeginWith';

    /** @var string */
    public const FILTER_CONTAINS = 'contains';

    /** @var string */
    public const FILTER_DO_NOT_CONTAINS = 'doNotContains';

    /** @var string */
    public const FILTER_END_WITH = 'endWith';

    /** @var string */
    public const FILTER_DO_NOT_END_WITH = 'doNotEndWith';

    /** @var string */
    public const FILTER_IS_EMPTY = 'isEmpty';

    /** @var string */
    public const FILTER_IS_NOT_EMPTY = 'isNotEmpty';

    /** @var array */
    public const FILTER_SIMPLE_TYPES = [
        self::FILTER_EQUAL            => '= ?',
        self::FILTER_NOT_EQUAL        => '!= ?',
        self::FILTER_LESS             => '< ?',
        self::FILTER_LESS_OR_EQUAL    => '<= ?',
        self::FILTER_GREATER          => '> ?',
        self::FILTER_GREATER_OR_EQUAL => '>= ?',
    ];

    /** @var array */
    public const FILTER_LIKE_TYPES = [
        self::FILTER_BEGIN_WITH => '?%',
        self::FILTER_CONTAINS   => '%?%',
        self::FILTER_END_WITH   => '%?',
    ];

    /** @var array */
    public const FILTER_NOT_LIKE_TYPES = [
        self::FILTER_DO_NOT_BEGIN_WITH => '?%',
        self::FILTER_DO_NOT_CONTAINS   => '%?%',
        self::FILTER_DO_NOT_END_WITH   => '%?',
    ];

    /** @var array */
    public const FILTER_ARRAY_TYPES = [
        self::FILTER_IN     => 'IN ?',
        self::FILTER_NOT_IN => 'NOT ' . self::FILTER_IN,
    ];

    /** @var array */
    public const FILTER_NO_ARG_TYPES = [
        self::FILTER_IS_NULL      => 'IS NULL',
        self::FILTER_IS_NOT_NULL  => 'IS NOT NULL',
        self::FILTER_IS_EMPTY     => '= ""',
        self::FILTER_IS_NOT_EMPTY => '!= ""',
    ];

    /** @var CSQLDataSource */
    private $ds;

    /** @var array */
    private $filters = [];

    /** @var int */
    private $position = 0;


    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if ($_filter = $request->query->get(static::QUERY_KEYWORD_FILTER)) {
            $this->createFilters(explode(self::FILTER_SEPARATOR, $_filter));
        }
    }

    /**
     * @return array
     */
    public function getExistingFilters(): array
    {
        return array_merge(
            self::FILTER_SIMPLE_TYPES,
            self::FILTER_LIKE_TYPES,
            self::FILTER_NOT_LIKE_TYPES,
            self::FILTER_ARRAY_TYPES,
            self::FILTER_NO_ARG_TYPES
        );
    }

    /**
     * @return array|CFIlter
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $query_filters
     *
     * @return void
     */
    private function createFilters(array $query_filters): void
    {
        foreach ($query_filters as $_filter) {
            $filter_parts = $this->getFilterParts($_filter);
            $filter_parts = array_map('urldecode', $filter_parts);

            $this->addFilter(
                new CFilter(
                    trim(str_replace('`', '', $filter_parts[0])),
                    trim($filter_parts[1]),
                    array_slice($filter_parts, 2)
                )
            );
        }
    }

    /**
     * Get the SQL representation of the filters
     *
     * @param CSQLDataSource $ds
     * @param callable[]     $sanitize

     * @return array
     * @throws CApiRequestException
     */
    public function getSqlFilter(CSQLDataSource $ds, array $sanitize = []): ?array
    {
        $this->ds = $ds;

        $where = [];

        /** @var CFilter $_filter */
        foreach ($this->filters as $_filter) {
            // Continue on empty filter or empty operator
            if (!$_filter->getKey() || !$_filter->getOperator()) {
                throw new CApiRequestException('Filter must have a key and an operator');
            }

            $field_name = $_filter->getKey();
            $operator   = $_filter->getOperator();
            $values     = $_filter->getValues();

            // sanitize values
            foreach ($sanitize as $function) {
              foreach ($values as $key => $value) {
                $values[$key] = call_user_func($function, $value);
              }
            }

            $result = null;
            if (array_key_exists($operator, self::FILTER_SIMPLE_TYPES)) {
                $result = $this->createSimpleFilter($field_name, $operator, $values);
            } elseif (array_key_exists($operator, self::FILTER_LIKE_TYPES)) {
                $result = $this->createLikeFilter($field_name, $operator, $values);
            } elseif (array_key_exists($operator, self::FILTER_NOT_LIKE_TYPES)) {
                $result = $this->createLikeFilter($field_name, $operator, $values, true);
            } elseif (array_key_exists($operator, self::FILTER_ARRAY_TYPES)) {
                $result = $this->createArrayFilter($field_name, $operator, $values);
            } elseif (array_key_exists($operator, self::FILTER_NO_ARG_TYPES)) {
                $result = $this->createNoArgFilter($field_name, $operator);
            } else {
                throw new CApiRequestException("Invalide operator {$operator}");
            }

            if ($result !== null) {
                $where[] = $result;
            }
        }

        return $where;
    }

    /**
     * @param string $field_name
     * @param string $operator
     * @param array  $values
     *
     * @return string
     */
    private function createSimpleFilter(string $field_name, string $operator, array $values = []): ?string
    {
        if (!$values) {
            return null;
        }

        $sql_operator = self::FILTER_SIMPLE_TYPES[$operator];

        $value = reset($values);

        return "`$field_name` " . $this->ds->prepare("$sql_operator", trim($value));
    }

    /**
     * @param string $field_name
     * @param string $operator
     * @param array  $values
     * @param bool   $not Negate the like
     *
     * @return string
     */
    private function createLikeFilter(
        string $field_name,
        string $operator,
        array $values = [],
        bool $not = false
    ): ?string {
        if (!$values) {
            return null;
        }

        $sql_operator = ($not) ? self::FILTER_NOT_LIKE_TYPES[$operator] : self::FILTER_LIKE_TYPES[$operator];

        $value = reset($values);

        return "`$field_name` " . (($not) ? 'NOT ' : '') . $this->ds->prepareLike(
                str_replace('?', trim($value), $sql_operator)
            );
    }

    /**
     * @param string $field_name
     * @param string $operator
     *
     * @return string
     */
    private function createNoArgFilter(string $field_name, string $operator): string
    {
        $sql_operator = self::FILTER_NO_ARG_TYPES[$operator];

        return "`$field_name` {$sql_operator}";
    }

    /**
     * @param string $field_name
     * @param string $operator
     * @param array  $parts
     *
     * @return string
     */
    private function createArrayFilter(string $field_name, string $operator, array $parts): ?string
    {
        if (empty($parts) || (count($parts) === 1 && $parts[0] === '')) {
            return null;
        }

        $query = "`$field_name` ";

        array_walk($parts, 'trim');

        if ($operator == self::FILTER_IN) {
            $query .= $this->ds->prepareIn($parts);
        } else {
            $query .= $this->ds->prepareNotIn($parts);
        }

        return $query;
    }

    /**
     * @param string $filter
     *
     * @return array|null
     */
    private function getFilterParts(string $filter): ?array
    {
        return explode(self::FILTER_PART_SEPARATOR, $filter);
    }


    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->filters[$this->position];
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
        return isset($this->filters[$this->position]);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->filters  = array_values($this->filters);
        $this->position = 0;
    }

    /**
     * @param CFilter $filter
     *
     * @return void
     */
    public function addFilter(CFilter $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * @param int  $i
     * @param bool $reindex
     *
     * @return void
     * @throws CApiRequestException
     */
    public function removeFilter(int $i, bool $reindex = false): void
    {
        if (!isset($this->filters[$i])) {
            throw new CApiRequestException("No filter at index {$i}");
        }

        unset($this->filters[$i]);

        // Reindex array
        if ($reindex) {
            $this->filters = array_values($this->filters);
        }
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->filters);
    }
}
