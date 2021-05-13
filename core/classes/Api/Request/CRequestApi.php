<?php

/**
 * @package Mediboard\Core\Api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Request;

use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Exceptions\CApiRequestException;
use Ox\Core\CSQLDataSource;
use Ox\Mediboard\Etablissement\CGroups;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CRequestApi
 */
class CRequestApi
{

    /** @var Request */
    private $request;

    /** @var CRequestFormats */
    private $request_formats;

    /** @var CRequestLimit */
    private $request_limit;

    /** @var CRequestSort */
    private $request_sort;

    /** @var CRequestRelations */
    private $request_relations;

    /** @var CRequestFieldsets */
    private $request_fieldsets;

    /** @var CRequestFilter */
    private $request_filter;

    /** @var CRequestLanguages */
    private $request_languages;

    /** @var CRequestGroup */
    private $request_group;

    /**
     * CRequestApi constructor.
     *
     * @param Request $request
     *
     * @throws CApiRequestException
     */
    public function __construct(Request $request)
    {
        $this->request           = $request;
        $this->request_formats   = new CRequestFormats($request);
        $this->request_limit     = new CRequestLimit($request);
        $this->request_sort      = new CRequestSort($request);
        $this->request_relations = new CRequestRelations($request);
        $this->request_fieldsets = new CRequestFieldsets($request);
        $this->request_filter    = new CRequestFilter($request);
        $this->request_languages = new CRequestLanguages($request);
        $this->request_group     = new CRequestGroup($request);
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param string $parameter_name
     *
     * @return mixed
     * @throws CApiRequestException
     */
    public function getRequestParameter(string $parameter_name)
    {
        $parameters = get_object_vars($this);
        foreach ($parameters as $parameter) {
            if (!is_subclass_of($parameter, IRequestParameter::class)) {
                continue;
            }

            if (is_a($parameter, $parameter_name)) {
                return $parameter;
            }
        }

        throw new CApiRequestException(
            "Invalid parameter '{$parameter_name}', parameter must implement IRequestParameter"
        );
    }

    /**
     * @param bool   $json_decode
     * @param string $encode_to
     * @param string $encode_from
     *
     * @return false|mixed|resource|string|null
     */
    public function getContent(bool $json_decode = true, string $encode_to = null, string $encode_from = 'UTF-8')
    {
        $content = $this->request->getContent();

        if ($json_decode) {
            $content_decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content = $content_decoded;
            }
        }

        if ($encode_to) {
            array_walk_recursive(
                $content,
                function (&$item) use ($encode_to, $encode_from) {
                    $item = mb_convert_encoding($item, $encode_to, $encode_from);
                }
            );
        }

        return $content;
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->request_relations->getRelations();
    }

    /**
     * @param string $fieldset
     *
     * @return bool
     */
    public function hasRelation(string $relation): bool
    {
        return in_array($relation, $this->getRelations(), true);
    }

    /**
     * @return array
     */
    public function getRelationsExcluded(): array
    {
        return $this->request_relations->getRelationsExcludes();
    }

    /**
     * @return array
     */
    public function getFieldsets(): array
    {
        return $this->request_fieldsets->getFieldsets();
    }

    /**
     * @param string $fieldset
     *
     * @return bool
     */
    public function hasFiledset(string $fieldset): bool
    {
        return in_array($fieldset, $this->getFieldsets(), true);
    }

    /**
     * @return array
     */
    public function getFormats(): array
    {
        return $this->request_formats->getFormats();
    }

    /**
     * @return string
     */
    public function getFormatsExpected(): string
    {
        return $this->request_formats->getExpected();
    }

    /**
     * @return array|null
     */
    public function getSort(): ?array
    {
        return $this->request_sort->getFields();
    }

    /**
     * @param string $default
     *
     * @return null|string
     * @example [lorem asc, ipsum desc]
     */
    public function getSortAsSql(string $default = null): ?string
    {
        return $this->request_sort->getSqlOrderBy($default);
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->request_limit->getLimit();
    }

    /**
     * @return mixed|null
     */
    public function getOffset()
    {
        return $this->request_limit->getOffset();
    }

    /**
     * @return string
     * @example [offset, limit]
     */
    public function getLimitAsSql(): string
    {
        return $this->request_limit->getSqlLimit();
    }

    /**
     * @param CSQLDataSource $ds
     * @param callable[]     $sanitize
     *
     * @return array
     * @throws CApiRequestException
     */
    public function getFilterAsSQL(CSQLDataSource $ds, array $sanitize = []): array
    {
        return $this->request_filter->getSqlFilter($ds, $sanitize);
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->request_filter->getFilters();
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->request_languages->getLanguage();
    }

    /**
     * @return string|null
     */
    public function getLanguageExpected(): ?string
    {
        return $this->request_languages->getExpected();
    }

    /**
     * @return CRequestFilter
     */
    public function getRequestFilter(): CRequestFilter
    {
        return $this->request_filter;
    }

    public function getRequestEtags(): array
    {
        return $this->request->getETags();
    }

    public function getGroup(): CGroups
    {
        return $this->request_group->getGroup();
    }
}
