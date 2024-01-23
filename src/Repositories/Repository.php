<?php

declare(strict_types=1);

namespace Service\Repository\Repositories;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Contracts\BaseCacheContract;
use Service\Repository\Contracts\BaseRepositoryContract;
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
abstract class Repository implements BaseRepositoryContract, BaseCacheContract
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
     * The repository model search data structure.
     *
     * @var bool
     */
    protected bool $payloadCollect = false;

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
    public function setConnection($name): Repository|BaseRepositoryContract|static
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
    public function setRepositoryId($repositoryId): Repository|BaseRepositoryContract|static
    {
        $this->repositoryId = $repositoryId;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryException|BindingResolutionException
     */
    public function createModel()
    {
        if (is_string($entity = $this->getModel())) {
            if (!class_exists($class = '\\' . ltrim($entity, '\\'))) {
                throw new RepositoryException("Class {$entity} does NOT exist!");
            }

            $entity = $this->getContainer()->make($class);
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
     * {@inheritdoc}
     */
    public function getModel(): string
    {
        $entity = $this->getContainer('config')->get('repository.models');

        return $this->model ?: str_replace(['Repositories', 'Repository'], [$entity, ''], static::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setModel($model)
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
    public function setContainer(Container $container): Repository|BaseRepositoryContract|static
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
    public function getFillable(): ?array
    {
        $entity = $this->model();

        return $entity->getFillable() ?: null;
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
     * @return string
     * @throws RepositoryException
     */
    public function getMap(): string
    {
        return $this->model()->getMap();
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param ?string $alias
     * @param array $columns
     * @param null $indexBy The index for the from.
     *
     * @return QueryBuilder
     * @throws RepositoryException
     */
    protected function createQueryBuilder(
        string $alias = null,
        array $columns = [],
        $indexBy = null
    ): QueryBuilder
    {
        return DB::table($this->getTable(), $alias)->select($columns)->from(
            $this->getTable(),
            $alias
        )->useIndex(
            $indexBy
        );
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
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param ?string $alias
     * @param array $columns
     * @param null $indexBy The index for the from.
     *
     * @return EloquentBuilder
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    protected function createModelBuilder(
        string $alias = null,
        array $columns = [],
        $indexBy = null
    ): EloquentBuilder
    {
        return $this->getModelInstance()::query()->select($columns)->from($this->getTable(), $alias)->useIndex(
            $indexBy
        );
    }

    /**
     * @return Model
     * @throws BindingResolutionException
     */
    private function getModelInstance(): Model
    {
        return $this->getContainer()->make($this->getModel());
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
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    protected function executeCallback(string $class, string $method, array $args, Closure $closure): mixed
    {
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

        // We're done, let's clean up!
        $this->resetRepository();

        return $this->getPayloadCollect() ? recToRec($result) : $result;
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

    /**
     * {@inheritdoc}
     */
    public function getPayloadCollect(): bool
    {
        return $this->payloadCollect;
    }

    /**
     * @inheritdoc
     */
    public function setPayloadCollect(): Repository|BaseRepositoryContract|static
    {
        $this->payloadCollect = true;

        return $this;
    }
}
