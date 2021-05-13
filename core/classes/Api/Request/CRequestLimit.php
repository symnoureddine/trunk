<?php
/**
 * @package Mediboard\Core\Fractal
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class CRequestLimit
 */
class CRequestLimit implements IRequestParameter
{
    /** @var string */
    public const QUERY_KEYWORD_OFFSET = 'offset';

    /** @var string */
    public const QUERY_KEYWORD_LIMIT = 'limit';


    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /** @var bool */
    private $in_query;

    /** @var int */
    public const OFFSET_DEFAULT = 0;

    /** @var int */
    public const LIMIT_DEFAULT = 50;

    /** @var int */
    public const LIMIT_MAX = 100;

    /**
     * CRequestLimit constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->in_query = ($request->query->get(self::QUERY_KEYWORD_OFFSET) || $request->query->get(
                self::QUERY_KEYWORD_LIMIT
            ));

        $this->offset = (int)$request->query->get('offset', static::OFFSET_DEFAULT);
        $this->limit  = (int)$request->query->get('limit', static::LIMIT_DEFAULT);
        $this->limit  = $this->limit > static::LIMIT_MAX ? static::LIMIT_MAX : $this->limit;
    }

    /**
     * @return string
     * @example [offset, limit]
     */
    public function getSqlLimit(): string
    {
        return "{$this->offset},{$this->limit}";
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return mixed|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return bool
     */
    public function isInQuery()
    {
        return $this->in_query;
    }

}
