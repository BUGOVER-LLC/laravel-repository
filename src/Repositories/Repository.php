<?php

declare(strict_types=1);

namespace Service\Repository\Repositories;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Contracts\BaseCacheContract;
use Service\Repository\Contracts\EloquentRepositoryContract;
use Service\Repository\Contracts\RepositoryContract;
use Service\Repository\Exceptions\RepositoryException;
use Service\Repository\Traits\Cache;
use Service\Repository\Traits\Criteria;
use Service\Repository\Traits\Magick;

use function call_user_func;
use function is_string;

/**
 * Class Repository
 *
 * @package Service\Repository\Repositories
 *
 * @property array $whereJson
 * @property array $whereExists
 * @property array $whereJsonCount
 * @property array $orWhereJson
 * @property array $whereJsonNotIn
 * @property array $when
 * @property array $has
 * @property array $orHas
 * @property array $orWhereHas
 * @property array $doesntHave
 * @property array $orDoesntHave
 * @property array $whereDoesntHave
 * @property array $orWhereDoesntHave
 * @property array $hasMorph
 * @property array $whereHasMorph
 * @property array $whereBetween
 * @property array $orWhereBetween
 * @property array $whereNotBetween
 * @property array $whereDate
 * @property array $whereMonth
 * @property array $whereDay
 * @property array $whereTime
 * @property array $withCount
 * @property array $withExists
 * @property array $withMax
 * @property array $withMin
 * @property array $withAvg
 * @property array $withSum
 * @property array $whereRaw
 * @property array $orWhereRaw
 * @property array $except
 * @property array $relations
 * @property array $where
 * @property array $orWhere
 * @property array $whereIn
 * @property array $whereNotIn
 * @property array $whereHas
 * @property array $scopes
 * @property array $orderBy
 * @property array $groupBy
 * @property array $having
 * @property array $havingRaw
 * @property array $join
 * @property null|int $offset
 * @property null|int $limit
 * @property bool $withTrashed
 * @property bool $withoutScope
 */
abstract class Repository implements RepositoryContract, BaseCacheContract
{
    use Cache;
    use Criteria;
    use Magick;

    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;
    /**
     * The connection name for the repository.
     *
     * @var string
     */
    protected string $connection;
    /**
     * The repository identifier.
     *
     * @var string
     */
    protected string $repositoryId;
    /**
     * The repository model.
     *
     * @var string
     */
    protected string $model;
    /**
     * The repository model search data structure.
     *
     * @var array
     */
    protected array $fieldSearchable;

    /**
     * {@inheritdoc}
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection($name): Repository|EloquentRepositoryContract|static
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryId(): string
    {
        return $this->repositoryId ?: static::class;
    }

    /**
     * {@inheritdoc}
     */
    public function setRepositoryId($repositoryId): Repository|EloquentRepositoryContract|static
    {
        $this->repositoryId = $repositoryId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(): Model
    {
        if (is_string($entity = $this->getModel())) {
            if (!class_exists($class = '\\' . ltrim($entity, '\\'))) {
                throw new RepositoryException("Class {$entity} does NOT exist!");
            }

            try {
                $entity = $this->getContainer()->make($class);
            } catch (BindingResolutionException) {
            }
        }

        // Set the connection used by the model
        if (!empty($this->connection)) {
            $entity = $entity->setConnection($this->connection);
        }

        if (!$entity instanceof Model) {
            throw new RepositoryException(
                "Class {$entity} must be an instance of \\Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $entity;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        try {
            $entity = $this->getContainer('config')->get('repository.models');
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
        }

        return $this->model ?: str_replace(['Repositories', 'Repository'], [$entity, ''], static::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setModel($model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer($service = null)
    {
        /**
         * @noinspection OffsetOperationsInspection
         */
        return null === $service ? ($this->container ?: app()) : ($this->container[$service] ?: app($service));
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RepositoryException
     */
    public function getFillable(): array
    {
        $entity = $this->model();

        return $entity->getFillable() ?: [];
    }

    /**
     * {@inheritDoc}
     *
     * @return Model
     * @throws RepositoryException
     */
    public function model($attributes = []): Model
    {
        $entity = $this->getModel();
        $entity = new $entity($attributes);

        if (!$entity instanceof Model) {
            throw new RepositoryException(
                "Class {$entity} must be an instance of \\Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $entity;
    }

    /**
     * @inheritDoc
     * @throws RepositoryException
     */
    public function getKeyName(): ?string
    {
        return $this->model()->getKeyName();
    }

    /**
     * @return string|null
     * @throws RepositoryException
     */
    public function getMap(): ?string
    {
        if (method_exists($this->model(), 'getMap')) {
            return $this->model()->getMap();
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws RepositoryException
     */
    public function getTable(): ?string
    {
        return $this->model()->getTable();
    }

    /**
     * Execute given callback and return the result.
     *
     * @param string $class
     * @param string $method
     * @param array $args
     * @param Closure $closure
     *
     * @return mixed
     */
    protected function executeCallback(string $class, string $method, array $args, Closure $closure): mixed
    {
        try {
            $skip_uri = $this->getContainer('config')->get('repository.cache.skip_uri');

            // Check if cache is enabled
            if ($this->getCacheLifetime() && !$this->getContainer('request')->has($skip_uri)) {
                return $this->cacheCallback($class, $method, $args, $closure);
            }

            // Cache disabled, just execute query & return result
            /**
             * @noinspection VariableFunctionsUsageInspection
             */
            $result = call_user_func($closure);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
        }
        // We're done, let's clean up!
        $this->resetRepository();

        return $result;
    }

    /**
     * @return $this
     */
    protected function resetRepository(): self
    {
        $this->whereJson = [];
        $this->whereExists = [];
        $this->whereJsonNotIn = [];
        $this->orWhereJson = [];
        $this->whereJsonCount = [];
        $this->when = [];
        $this->has = [];
        $this->orHas = [];
        $this->orWhereHas = [];
        $this->doesntHave = [];
        $this->orDoesntHave = [];
        $this->whereDoesntHave = [];
        $this->orWhereDoesntHave = [];
        $this->hasMorph = [];
        $this->whereHasMorph = [];
        $this->whereBetween = [];
        $this->orWhereBetween = [];
        $this->whereNotBetween = [];
        $this->whereDate = [];
        $this->whereMonth = [];
        $this->whereDay = [];
        $this->whereTime = [];
        $this->withCount = [];
        $this->withExists = [];
        $this->withMax = [];
        $this->withMin = [];
        $this->withAvg = [];
        $this->withSum = [];
        $this->whereRaw = [];
        $this->orWhereRaw = [];
        $this->except = [];
        $this->relations = [];
        $this->where = [];
        $this->orWhere = [];
        $this->whereIn = [];
        $this->whereNotIn = [];
        $this->whereHas = [];
        $this->scopes = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->having = [];
        $this->havingRaw = [];
        $this->join = [];
        $this->offset = null;
        $this->limit = null;
        $this->withTrashed = false;
        $this->withoutScope = false;

        if (method_exists($this, 'flushCriteria')) {
            $this->flushCriteria();
        }

        return $this;
    }
}
