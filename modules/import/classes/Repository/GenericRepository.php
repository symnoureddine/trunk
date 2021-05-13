<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework\Repository;

use Exception;
use Generator;
use Ox\Import\Framework\Configuration\ConfigurableInterface;
use Ox\Import\Framework\Configuration\Configuration;
use Ox\Import\Framework\Configuration\ConfigurationTrait;
use Ox\Import\Framework\Entity\EntityInterface;
use Ox\Import\Framework\Mapper\MapperInterface;
use Ox\Import\Framework\Mapper\MapperBuilderInterface;

/**
 * Generic class to handle the mapper and retrieve data
 */
class GenericRepository implements RepositoryInterface, ConfigurableInterface
{
    use ConfigurationTrait;

    /** @var MapperBuilderInterface */
    private $builder;

    /** @var MapperInterface */
    protected $mapper;

    /** @var MapperInterface[] */
    protected $mapper_pool;

    /** @var string Import resource name */
    private $resource_name;

    /**
     * GenericExternalObjectRepository constructor.
     *
     * @param MapperBuilderInterface $builder
     * @param string                 $resource_name
     *
     * @throws Exception
     */
    public function __construct(MapperBuilderInterface $builder, string $resource_name)
    {
        //    if (!is_a($resource_name, EntityInterface::class, true)) {
        //      throw new Exception('not an external object');
        //    }

        $this->builder       = $builder;
        $this->resource_name = $resource_name;
        $this->mapper        = $this->buildMapper();
    }

    /**
     * @inheritDoc
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;

        if ($this->builder instanceof ConfigurableInterface) {
            $this->builder->setConfiguration($configuration);
        }

        if ($this->mapper instanceof ConfigurableInterface) {
            $this->mapper->setConfiguration($configuration);

            // Need to rebuild mapper on configuration change because of complex object creation
            $this->mapper = $this->buildMapper();
        }
    }

    /**
     * @return MapperInterface
     * @throws Exception
     */
    private function buildMapper(): MapperInterface
    {
        return $this->builder->build($this->resource_name);
    }

    /**
     * @inheritDoc
     */
    public function findById($id): ?EntityInterface
    {
        return $this->mapper->retrieve($id);
    }

    /**
     * Todo: Implement eager loading (mass load)
     * Todo: Implement sorting
     *
     * @inheritDoc
     */
    public function get(int $count = 1, int $offset = 0, $id = null): ?Generator
    {
        foreach ($this->mapper->get($count, $offset, $id) as $_object) {
            yield $_object;
        }
    }

    /**
     * @inheritDoc
     */
    public function findInPoolById($name, $id): ?EntityInterface
    {
        $mapper = ($this->mapper_pool[$name]) ?? $this->mapper_pool[$name] = $this->builder->build($name);

        return $mapper->retrieve($id);
    }

    public function findCollectionInPool($name): ?Generator
    {
        $mapper = ($this->mapper_pool[$name]) ?? $this->mapper_pool[$name] = $this->builder->build($name);

        foreach ($mapper->get(500) as $_mapper) {
            yield $_mapper;
        }
    }

    public static function getExternalClassFromType(string $type): ?string
    {
        switch ($type) {
            case 'utilisateur':
                return 'USER';
            case 'medecin':
            case 'medecin_user':
                return 'MEDC';
            case 'plage_consultation':
            case 'plage_consultation_autre':
                return 'PLGC';
            case 'patient':
                return 'PATI';
            case 'sejour':
                return 'SEJR';
            case 'consultation':
            case 'consultation_autre':
                return 'CSLT';
            case 'antecedent':
                return 'ATCD';
            case 'evenement_patient':
                return 'EVTPA';
            case 'intervention':
                return 'INTER';
            default:
                return null;
        }
    }
}
