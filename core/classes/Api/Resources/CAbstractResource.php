<?php

/**
 * @package Mediboard\\Api
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Api\Resources;

use DateTime;
use DateTimeZone;
use Exception;
use JsonSerializable;
use Ox\Core\Api\Exceptions\CApiException;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\Api\Request\CRequestFieldsets;
use Ox\Core\Api\Request\CRequestFormats;
use Ox\Core\Api\Request\CRequestRelations;
use Ox\Core\Api\Serializers\CAbstractSerializer;
use Ox\Core\Api\Serializers\CJsonApiSerializer;
use Ox\Core\Api\Transformers\CAbstractTransformer;
use Ox\Core\Api\Transformers\CArrayTransformer;
use Ox\Core\Api\Transformers\CModelObjectTransformer;
use Ox\Core\Api\Transformers\CObjectTransformer;
use Ox\Core\CMbArray;
use Ox\Core\CModelObject;

/**
 * Class CAbstractResource
 *
 */
abstract class CAbstractResource implements JsonSerializable
{

    /** @var string */
    public const TYPE_ITEM = 'item';

    /** @var string */
    public const TYPE_COLLECTION = 'collection';

    /** @var string */
    public const CURRENT_RESOURCE_NAME = 'current';

    /** @var string */
    protected $type;

    /** @var array */
    protected $datas;

    /** @var string */
    protected $name;

    /** @var string */
    protected $model_class;

    /** @var array|null */
    protected $model_fieldsets;

    /** @var array|null */
    protected $model_relations;

    /** @var string */
    protected $format;

    /** @var array */
    protected $links = [];

    /** @var array */
    protected $metas = [];

    /** @var string */
    protected $request_url;

    /** @var string */
    protected $serializer = CJsonApiSerializer::class;

    /** @var int */
    protected $recursion_depth = 0;

    /** @var string */
    protected $etag;

    /**
     * @param CRequestApi  $request
     * @param array|object $datas
     *
     * @return CItem
     * @throws CApiException
     */
    final public static function createFromRequest(CRequestApi $request, $datas): CAbstractResource
    {
        $instance_class = static::class;
        /** @var CAbstractResource $instance */
        $instance = new $instance_class($datas);
        if ($instance->isModelObjectResource()) {
            // relations from which we remove excluded relations
            $request_relations  = $request->getRelations();
            $excluded_relations = $request->getRelationsExcluded();

            if (!empty($request_relations)) {
                $request_relations = count($request_relations) === 1 ? reset($request_relations) : $request_relations;
                $instance->setModelRelations($request_relations, $excluded_relations);
            }

            // fieldsets
            $request_fieldsets = $request->getFieldsets();
            if (!empty($request_fieldsets)) {
                $request_filedsets = count($request_fieldsets) === 1 ? reset($request_fieldsets) : $request_fieldsets;
                $instance->setModelFieldsets($request_filedsets);
            }

            // include schema ?
            if ($request->getRequest()->query->getBoolean('schema', false)) {
                $instance->addMetasSchema();
            }
        }
        $instance->setFormat($request->getFormatsExpected());
        $instance->setRequestUrl($request->getRequest()->getUri());

        return $instance;
    }

    /**
     * CAbstractResource constructor.
     *
     * @param string      $type
     * @param mixed       $datas
     * @param null|string $model_class
     *
     * @throws CApiException
     */
    protected function __construct(string $type, $datas, string $model_class = null)
    {
        if ($type !== static::TYPE_COLLECTION && $type !== static::TYPE_ITEM) {
            throw new CApiException("Undefined resource type '{$type}'.");
        }

        if (!is_array($datas) && !is_object($datas)) {
            throw new CApiException('Resource datas must be an array or an object');
        }

        $this->type        = $type;
        $this->datas       = $datas;
        $this->model_class = $model_class;

        $this->setDefaultMetas();
    }

    /**
     * @return array
     */
    abstract public function transform(): array;

    /**
     * @return CAbstractTransformer
     */
    protected function createTransformer(): CAbstractTransformer
    {
        if (!$this->model_class) {
            $transformer = CArrayTransformer::class;
        } elseif (is_subclass_of($this->model_class, CModelObject::class)) {
            $transformer = CModelObjectTransformer::class;
        } else {
            $transformer = CObjectTransformer::class;
        }

        return new $transformer($this);
    }

    /**
     * @param string $format
     *
     * @return CAbstractResource
     * @throws CApiException
     */
    public function setFormat(string $format): CAbstractResource
    {
        if (!in_array($format, CRequestFormats::FORMATS, true)) {
            throw new CApiException('Invalid resource format, use CRequestFormats contantes.');
        }
        $this->format = $format;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return CAbstractResource
     */
    public function setRequestUrl(string $url): CAbstractResource
    {
        $this->request_url = $url;

        return $this;
    }

    /**
     * Groups of fileds
     *
     * @param array|string $fieldsets
     *
     * @return CAbstractResource
     * @throws CApiException
     */
    public function setModelFieldsets($fieldsets): CAbstractResource
    {
        if (empty($this->datas)) {
            return $this;
        }

        if (!$this->isModelObjectResource()) {
            throw new CApiException('Set models groups only for CModelObject resource.');
        }

        $available_fieldsets = ($this->model_class)::getConstants('FIELDSET');

        if (is_string($fieldsets)) {
            switch ($fieldsets) {
                case CRequestFieldsets::QUERY_KEYWORD_NONE:
                    $fieldsets = [];
                    break;
                case $fieldsets === CRequestFieldsets::QUERY_KEYWORD_ALL:
                    $fieldsets = $available_fieldsets;
                    break;
                default:
                    $fieldsets = [$fieldsets];
                    break;
            }
        }

        // filter
        $fieldsets_relations = $this->formatFieldsetByRelation($fieldsets);

        // check current fieldsets
        $this->checkFieldsets($fieldsets_relations[self::CURRENT_RESOURCE_NAME] ?? [], $available_fieldsets);

        $this->model_fieldsets = $fieldsets_relations;

        return $this;
    }

    /**
     * @param array $fieldsets
     *
     * @return array
     */
    private function formatFieldsetByRelation(array $fieldsets): array
    {
        $fieldsets_relations = [];
        foreach ($fieldsets as $fieldset) {
            // get resource name
            [$resource_name, $explode_fieldset] = $this->separateFieldsetAndRelation($fieldset);

            // get fieldset
            $fieldset = count($explode_fieldset) > 1 ? implode(
                '.',
                array_slice($explode_fieldset, 1)
            ) : $explode_fieldset[0];

            $fieldsets_relations[$resource_name][] = $fieldset;
        }

        // unique fieldset
        foreach ($fieldsets_relations as $relation_name => $fieldsets) {
            $fieldsets_relations[$relation_name] = array_unique($fieldsets);
        }

        return $fieldsets_relations;
    }

    /**
     * @param array $fieldsets
     * @param array $available_fieldsets
     *
     * @return void
     * @throws CApiException
     */
    private function checkFieldsets(array $fieldsets, array $available_fieldsets): void
    {
        foreach ($fieldsets as $fieldset) {
            if ($fieldset === CRequestFieldsets::QUERY_KEYWORD_NONE) {
                throw new CApiException("Unexpected reserved fieldsets 'none' in multiple declaration.");
            }

            if ($fieldset === CRequestFieldsets::QUERY_KEYWORD_ALL) {
                throw new CApiException("Unexpected reserved fieldsets 'all' in multiple declaration.");
            }

            if (!in_array($fieldset, $available_fieldsets, true)) {
                throw new CApiException("Undefined fieldset '{$fieldset}' in class '{$this->model_class}'.");
            }
        }
    }

    /**
     * @param string $fieldset
     *
     * @return array
     */
    private function separateFieldsetAndRelation(string $fieldset): array
    {
        $explode_fieldset = explode('.', $fieldset);
        $resource_name    = count($explode_fieldset) > 1 ? $explode_fieldset[0] : self::CURRENT_RESOURCE_NAME;

        return [$resource_name, $explode_fieldset];
    }

    /**
     * Includes resource
     *
     * @param array|string $relations
     * @param array        $excluded_relations
     *
     * @return CAbstractResource
     *
     * @throws CApiException
     */
    public function setModelRelations($relations, $excluded_relations = []): CAbstractResource
    {
        if (empty($this->datas)) {
            return $this;
        }

        if (!$this->isModelObjectResource()) {
            throw new CApiException('Set models relations only for CModelObject resource.');
        }

        $available_relations = ($this->model_class)::getConstants('RELATION');

        if (is_string($relations)) {
            switch ($relations) {
                case CRequestRelations::QUERY_KEYWORD_NONE:
                    $relations = [];
                    break;
                case CRequestRelations::QUERY_KEYWORD_ALL:
                    $relations = $available_relations;
                    break;
                default:
                    $relations = [$relations];
                    break;
            }
        }

        foreach ($relations as $key => $relation) {
            if ($relation === CRequestRelations::QUERY_KEYWORD_NONE) {
                throw new CApiException("Unexpected reserved relation 'none' in multiple declaration.");
            }

            if ($relation === CRequestRelations::QUERY_KEYWORD_ALL) {
                throw new CApiException("Unexpected reserved relation 'all' in multiple declaration.");
            }

            if (!in_array($relation, $available_relations, true)) {
                throw new CApiException("Undefined relation '{$relation}' in class '{$this->model_class}'.");
            }

            if (in_array($relation, $excluded_relations)) {
                unset($relations[$key]);
            }
        }

        $this->model_relations = $relations;

        return $this;
    }

    /**
     * @return bool
     */
    public function isModelObjectResource(): bool
    {
        return $this->model_class && is_subclass_of($this->model_class, CModelObject::class);
    }


    /**
     * @param string $name
     *
     * @return CAbstractResource
     */
    public function setName($name): CAbstractResource
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * @return array
     */
    public function getModelFieldsets(): ?array
    {
        return $this->model_fieldsets;
    }

    /**
     * @param string $resource_name
     *
     * @return mixed|null
     */
    public function getFieldsetsByRelation(string $resource_name = self::CURRENT_RESOURCE_NAME)
    {
        return $this->model_fieldsets[$resource_name] ?? null;
    }

    /**
     * @param array|string $fieldsets
     *
     * @return array
     * @throws CApiException
     */
    public function addModelFieldset($fieldsets)
    {
        if (is_string($fieldsets)) {
            $fieldsets = [$fieldsets];
        }

        $available_fieldsets = ($this->model_class)::getConstants('FIELDSET');

        foreach ($fieldsets as $fieldset) {
            // get resource name
            [$resource_name, $explode_fieldset] = $this->separateFieldsetAndRelation($fieldset);

            // Check current fieldsets
            if ($resource_name === self::CURRENT_RESOURCE_NAME) {
                $this->checkFieldsets($explode_fieldset, $available_fieldsets);
            }

            // init array
            if (!$this->model_fieldsets || !isset($this->model_fieldsets[$resource_name])) {
                $this->model_fieldsets[$resource_name] = [];
            }

            $fieldset = count($explode_fieldset) > 1 ? array_slice($explode_fieldset, 1) : $explode_fieldset;

            // add fieldset
            $this->model_fieldsets[$resource_name] = array_unique(
                array_merge($this->model_fieldsets[$resource_name], $fieldset)
            );
        }

        return $this->model_fieldsets;
    }

    /**
     * @param string|array $fieldsets
     *
     * @return bool
     */
    public function removeModelFieldset($fieldsets): bool
    {
        if (is_string($fieldsets)) {
            $fieldsets = [$fieldsets];
        }

        $count = 0;
        foreach ($fieldsets as $fieldset) {
            // get resource name
            [$resource_name, $explode_fieldset] = $this->separateFieldsetAndRelation($fieldset);

            // check has fieldset for resource name
            if (!$this->getFieldsetsByRelation($resource_name)) {
                continue;
            }

            $fieldset = implode(
                ".",
                count($explode_fieldset) > 1 ? array_slice($explode_fieldset, 1) : $explode_fieldset
            );

            // remove fieldset for resource name
            $count                                 += CMbArray::removeValue(
                $fieldset,
                $this->model_fieldsets[$resource_name]
            );
            $this->model_fieldsets[$resource_name] = array_values($this->model_fieldsets[$resource_name]);
        }

        return $count == count($fieldsets);
    }

    /**
     * @param string $relation_name
     *
     * @return bool
     */
    public function hasModelrelation(string $relation_name): bool
    {
        return in_array($relation_name, $this->model_relations ?? []);
    }

    /**
     * @param string $fieldset
     * @param string $relation_name
     *
     * @return bool
     */
    public function hasModelFieldset(string $fieldset): bool
    {
        [$relation_name, $fieldset] = $this->separateFieldsetAndRelation($fieldset);

        $fieldset = $relation_name !== self::CURRENT_RESOURCE_NAME ? implode(
            '.',
            array_slice($fieldset, 1)
        ) : $fieldset[0];

        return in_array($fieldset, $this->getFieldsetsByRelation($relation_name) ?? [], true);
    }

    /**
     * @return array
     */
    public function getModelRelations(): ?array
    {
        return $this->model_relations;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @return array
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * @return void
     */
    protected function setDefaultMetas(): void
    {
        try {
            $dt   = new DateTime('now', new DateTimeZone('Europe/Paris'));
            $date = $dt->format('Y-m-d H:i:sP');
        } catch (Exception $exception) {
            $date = null;
        }

        $this->metas['date']      = $date;
        $this->metas['copyright'] = 'OpenXtrem-' . date('Y');
        $this->metas['authors']   = 'dev@openxtrem.com';
    }

    /**
     * @param string       $key
     * @param string|array $value
     *
     * @return CAbstractResource
     */
    public function addMeta($key, $value): CAbstractResource
    {
        $this->metas[$key] = $value;

        return $this;
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public function addMetas(array $array): CAbstractResource
    {
        foreach ($array as $key => $value) {
            $this->addMeta($key, $value);
        }

        return $this;
    }

    /**
     * @param array $links
     *
     * @return CAbstractResource
     */
    public function addLinks(array $links): CAbstractResource
    {
        foreach ($links as $member => $link) {
            $this->links[$member] = $link;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getRecursionDepth(): int
    {
        return $this->recursion_depth;
    }

    /**
     * @param int $recursion_depth
     *
     * @return CAbstractResource
     */
    public function setRecursionDepth(int $recursion_depth): self
    {
        $this->recursion_depth = $recursion_depth;

        return $this;
    }

    /**
     * Serialize resource to json:api formats
     * Merge top level & resource objects
     *
     * @return array
     */
    public function serialize(): array
    {
        return $this->createSerializer()->serialize();
    }

    /**
     * @return string
     */
    public function getSerializer(): string
    {
        return $this->serializer;
    }

    /**
     * @param string $serializer
     *
     * @return void
     * @throws CApiException
     */
    public function setSerializer(string $serializer): void
    {
        if (!is_subclass_of($serializer, CAbstractSerializer::class)) {
            throw new CApiException('Invalid serializer ' . $serializer);
        }
        $this->serializer = $serializer;
    }

    /**
     * @return CAbstractSerializer
     */
    public function createSerializer(): CAbstractSerializer
    {
        return new $this->serializer($this);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $datas = $this->serialize();

        $datas = CMbArray::utf8Encoding($datas, true);

        return $datas;
    }

    /**
     * @return array|mixed
     */
    public function xmlSerialize()
    {
        return $this->jsonSerialize();
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->request_url;
    }

    /**
     * @param string $relation
     * @param array  $fieldsets
     *
     * @return CAbstractResource
     * @throws CApiException
     */
    public function addFieldsetsOnRelation(
        string $relation,
        array $fieldsets
    ): CAbstractResource {
        if (!$this->hasModelrelation($relation)) {
            return $this;
        }
        $this->addModelFieldset(
            array_map(
                function ($fieldset) use ($relation) {
                    return $relation . "." . $fieldset;
                },
                $fieldsets
            )
        );

        return $this;
    }

    /**
     * @param bool $model_schema
     *
     * @return $this
     */
    public function addMetasSchema(): CAbstractResource
    {
        if ($this->isModelObjectResource()) {
            /** @var CModelObject $model */
            $model                 = new $this->model_class();
            $this->metas['schema'] = $model->getSchema($this->getModelFieldsets());
        }

        return $this;
    }


    public function getDatasHash(): string
    {
        return md5(serialize([$this->datas, $this->request_url]));
    }

    public function isEtaggable(): bool
    {
        return (bool)$this->etag;
    }

    /**
     * @use Const CEtag::TYPE_*
     */
    public function setEtag(string $etag_type): void
    {
        $this->etag = $etag_type . ':' . $this->getDatasHash();
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }
}
