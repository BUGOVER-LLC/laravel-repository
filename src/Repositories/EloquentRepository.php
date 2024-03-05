<?php

declare(strict_types=1);

namespace Service\Repository\Repositories;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Contracts\EloquentRepositoryContract;
use Service\Repository\Contracts\WhereClauseContract;
use Service\Repository\Exceptions\EntityNotFoundException;
use Service\Repository\Exceptions\RepositoryException;
use Service\Repository\Traits\Clauses;
use Service\Repository\Traits\Prepare;
use Service\Repository\Traits\Store;
use Service\Repository\Traits\StoreRelations;

use function count;
use function func_get_args;
use function is_array;

/**
 * Class BaseRepository
 *
 * @method  distanceCord($latitude, $longitude, string $distance = 1)
 * @method  distance($latitude, $longitude)
 * @method  withoutGlobalScopes($scopes = null)
 */
class EloquentRepository extends Repository implements WhereClauseContract, EloquentRepositoryContract
{
    use Prepare;
    use Clauses;
    use Store;
    use StoreRelations;

    /**
     * @inheritDoc
     * @param null $column
     * @param array $attributes
     * @return object|null
     */
    public function firstLatest($column = null, array $attributes = ['*']): ?object
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->latest($column)->first($attributes));
    }

    /**
     * @inheritDoc
     * @param null $column
     * @return mixed
     */
    public function latest($column = null): Builder
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->latest($column));
    }

    /**
     * @inheritDoc
     */
    public function firstOldest($column = null): ?object
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->oldest($column)->first());
    }

    /**
     * @inheritDoc
     */
    public function oldest($column = null): Builder
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->oldest($column));
    }

    /**
     * {@inheritdoc}
     */
    public function firstWhere(array $where, $attributes = ['*']): ?object
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            function () use ($where, $attributes) {
                [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

                $this->where($attribute, $operator, $value, $boolean);

                return $this->prepareQuery($this->createModel())->first($attributes);
            });
    }

    /**
     * @param $against
     * @param ...$matches
     * @return WhereClauseContract|null
     */
    public function fullSearch($against, ...$matches): ?static
    {
        return $this->whereRaw("MATCH ($matches) AGAINST (\\'$against\\' IN BOOLEAN MODE)");
    }

    /**
     * @param $attribute
     * @param $operator
     * @param $value
     * @param $exists_column
     * @param string $boolean
     * @return bool
     */
    public function whereExistsExist(
        $attribute,
        $operator = null,
        $value = null,
        $exists_column = '',
        string $boolean = 'and'
    ): bool {
        return $this->where($attribute, $operator, $value, $boolean)->exists($exists_column);
    }

    /**
     * @param string $column
     * @return bool
     */
    public function exists(string $column = '*'): bool
    {
        return (bool)$this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->exists($column));
    }

    /////////////////////////         RESET WHERE CLAUSES          /////////////////////////

    /**
     * @param $id
     * @param $attributes
     * @return mixed|object|null
     */
    public function findOrFail($id, $attributes = ['*']): mixed
    {
        $result = $this->find($id, $attributes);

        if (is_array($id) && count($result) === count(array_unique($id))) {
            return $result;
        }

        if (null !== $result) {
            return $result;
        }

        throw new EntityNotFoundException($this->getModel(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, $attrs = ['*']): ?object
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->find($id, $attrs));
    }

    /**
     * @inheritDoc
     */
    public function findOrNew(int $id, array $attributes = ['*'], bool $sync_relations = false): ?object
    {
        if (null !== ($entity = $this->find($id, $attributes))) {
            return $entity;
        }

        return $this->create($attributes, $sync_relations);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $attribute, mixed $value, array $attributes = ['*']): object|null
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->where($attribute, '=', $value)->first($attributes));
    }

    /**
     * {@inheritdoc}
     */
    public function findFirst($attr = ['*']): object|null
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->first($attr));
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(
        int|string $per_page = null,
        array $attributes = ['*'],
        string $page_name = 'page',
        null|int|string $page = null
    ): LengthAwarePaginator {
        $page = $page ?: Paginator::resolveCurrentPage($page_name);

        return $this->executeCallback(static::class, __FUNCTION__, array_merge(func_get_args(), compact('page')),
            fn() => $this->prepareQuery($this->createModel())->paginate($per_page, $attributes, $page_name, $page));
    }

    /**
     * {@inheritdoc}
     */
    public function simplePaginate(
        int|string $per_page = null,
        array $attributes = ['*'],
        string $page_name = 'page',
        null|int|string $page = null
    ): \Illuminate\Contracts\Pagination\Paginator {
        $page = $page ?: Paginator::resolveCurrentPage($page_name);

        return $this->executeCallback(static::class, __FUNCTION__, array_merge(func_get_args(), compact('page')),
            fn() => $this->prepareQuery($this->createModel())->simplePaginate($per_page, $attributes, $page_name,
                $page));
    }

    /**
     * {@inheritdoc}
     */
    public function cursorPaginate(
        int|string $per_page = null,
        array $columns = ['*'],
        string $cursor_name = 'cursor',
        $cursor = null
    ) {
        $cursor = $cursor ?: Paginator::resolveCurrentPage($cursor_name);

        return $this->executeCallback(static::class, __FUNCTION__, array_merge(func_get_args(), compact('cursor')),
            fn() => $this->prepareQuery($this->createModel())->cursorPaginate($per_page, $columns, $cursor_name,
                $cursor));
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $where, $attrs = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function () use ($where, $attrs) {
            [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

            $this->where($attribute, $operator, $value, $boolean);

            return $this->prepareQuery($this->createModel())->get($attrs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findWhereIn(array $where, $attrs = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function () use ($where, $attrs) {
            [$attribute, $values, $boolean, $not] = array_pad($where, 4, null);

            $this->whereIn($attribute, $values, $boolean, $not);

            return $this->prepareQuery($this->createModel())->get($attrs);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findWhereNotIn(array $where, $attributes = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            function () use ($where, $attributes) {
                [$attribute, $values, $boolean] = array_pad($where, 3, null);

                $this->whereNotIn($attribute, $values, $boolean);

                return $this->prepareQuery($this->createModel())->get($attributes);
            });
    }

    /**
     * @inheritdoc
     */
    public function findWhereHas(array $where, $attributes = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            function () use ($where, $attributes) {
                [$relation, $callback, $operator, $count] = array_pad($where, 4, null);

                $this->whereHas($relation, $callback, $operator, $count);

                return $this->prepareQuery($this->createModel())->get($attributes);
            });
    }

    /**
     * @inheritdoc
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function updateOrCreate(
        array $where,
        array $attrs,
        bool $sync_relations = false,
        bool $merge = false
    ): ?object {
        $queries_chunk = array_chunk($where, 3);

        if (1 < count($queries_chunk)) {
            foreach ($queries_chunk as $query) {
                $this->where($query[0], $query[1], $query[2]);
            }
        } else {
            $this->where($queries_chunk[0][0], $queries_chunk[0][1], $queries_chunk[0][2]);
        }

        $result = null;
        $entities = $this->findAll();

        if (1 < $entities->count()) {
            foreach ($entities as $entity) {
                $result = $this->update($entity->{$entity->getKeyName()}, $attrs, $sync_relations);
            }
        } elseif (1 === $entities->count()) {
            $query_attribute[$queries_chunk[0][0]] = $queries_chunk[0][2];
            $attribute = array_merge($query_attribute, $attrs);
            $result = $this->update($entities[0]->{$entities[0]->getKeyName()}, $attribute, $sync_relations);
        } else {
            $query_attribute[$queries_chunk[0][0]] = $queries_chunk[0][2];
            $attribute = array_merge($query_attribute, $attrs);
            $result = $this->create($attribute, $sync_relations);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($attr = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->get($attr));
    }

    /**
     * {@inheritdoc}
     */
    public function count($columns = '*'): int
    {
        return (int)$this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->count($columns));
    }

    /**
     * @inheritDoc
     */
    public function store(int $id = null, array $attrs = [], bool $sync_relations = false): null|object|bool
    {
        return !$id ? $this->create($attrs, $sync_relations) : $this->update($id, $attrs, $sync_relations);
    }

    /**
     * @inheritDoc
     */
    public function updateOrInsert($attrs, $values = [], $sync_relations = false): null|object|bool
    {
        $query = array_chunk($attrs, 3);

        if (count($query) > 1) {
            foreach ($query as $query_result) {
                $this->where($query_result[0], $query_result[1], $query_result[2]);
            }
        } else {
            $this->where($query[0][0], $query[0][1], $query[0][2]);
        }

        $result = null;
        $entities = $this->findAll();

        if ($entities->count()) {
            foreach ($entities as $entity) {
                $result = $this->update($entity->{$entity->getKeyName()}, $attrs, $sync_relations);
            }
        } else {
            $query_attribute[$query[0][0]] = $query[0][2];
            $attribute = array_merge($attrs, $query_attribute);
            $result = $this->insert($attribute);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(Closure $closure = null, Closure $closure_before = null, int $tries = 1)
    {
        if ($closure_before) {
            $closure_before();
        }

        if ($closure) {
            return $this->getContainer('db')->transaction($closure, $tries);
        }

        $this->getContainer('db')->beginTransaction();
    }

    /**
     * @inheritdoc
     */
    public function commit(): void
    {
        $this->getContainer('db')->commit();
    }

    /**
     * @inheritdoc
     */
    public function rollBack(): void
    {
        $this->getContainer('db')->rollBack();
    }

    /**
     * @inheritdoc
     */
    public function min(string $column)
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->min($column));
    }

    /**
     * @inheritdoc
     */
    public function max(string $column): mixed
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->max($column));
    }

    /**
     * @inheritdoc
     */
    public function avg(string $column): mixed
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(), function () use ($column) {
            return $this->prepareQuery($this->createModel())->avg($column);
        });
    }

    /**
     * @inheritdoc
     */
    public function sum(string $column): mixed
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->sum($column));
    }

    /**
     * @inheritDoc
     */
    public function deletesBy(string $where, array $values = []): ?bool
    {
        return $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->whereIn($where, $values)->delete());
    }
}
