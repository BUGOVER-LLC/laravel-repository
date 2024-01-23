<?php

declare(strict_types=1);

namespace Service\Repository\Repositories;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Contracts\WhereClauseContract;
use Service\Repository\Exceptions\EntityNotFoundException;
use Service\Repository\Exceptions\RepositoryException;
use Service\Repository\Traits\Clauses;
use Service\Repository\Traits\Prepare;
use Service\Repository\Traits\RelationsStore;

use function func_get_args;
use function in_array;
use function is_array;

/**
 * Class BaseRepository
 *
 * @method  distanceCord($latitude, $longitude, string $distance = 1)
 * @method  distance($latitude, $longitude)
 * @method  withoutGlobalScopes($scopes = null)
 */
class BaseRepository extends Repository
{
    use Clauses;
    use Prepare;
    use RelationsStore;

    /**
     * @inheritDoc
     * @param null $column
     * @param array $attributes
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function firstLatest($column = null, array $attributes = ['*']): ?object
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->latest($column)->first($attributes)
        );
    }

    /**
     * @inheritDoc
     * @param null $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function latest($column = null): Builder
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->latest($column)
        );
    }

    /**
     * @inheritDoc
     * @param null $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function firstOldest($column = null): ?object
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->oldest($column)->first()
        );
    }

    /**
     * @inheritDoc
     * @param null $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function oldest($column = null): Builder
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->oldest($column)
        );
    }

    /**
     * @inheritDoc
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function updateSet(array $attrs = [], bool $syncRelations = false): Collection|bool
    {
        $this->prepareQuery($this->model());

        $entities = $this->findAll();

        if (1 > $entities->count()) {
            // empty Collection
            return $entities;
        }

        $updated = [];

        foreach ($entities as $entity) {
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.updating', [$this, $entity]);

            // Extract relationships
            if ($syncRelations) {
                $relations = $this->extractRelations($entity, $attrs);
                Arr::forget($attrs, array_keys($relations));
            }

            // Fill instance with data
            $entity->fill($attrs);

            // Update the instance
            $updated[] = $entity->save();

            // Sync relationships
            if ($syncRelations && isset($relations)) {
                $this->syncRelations($entity, $relations);
            }

            if ($updated) {
                // Fire the updated event
                $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.updated', [$this, $entity]);
            }
        }

        return !in_array(false, $updated, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $attr
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findAll($attr = ['*']): Collection
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->get($attr)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $columns
     * @return int
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function count($columns = '*'): int
    {
        return (int) $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->count($columns)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array $where
     * @param string[] $attributes
     * @return mixed
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function firstWhere(array $where, $attributes = ['*']): ?object
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($where, $attributes) {
                [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

                $this->where($attribute, $operator, $value, $boolean);

                return $this->prepareQuery($this->createModel())->first($attributes);
            }
        );
    }

    /**
     * @return bool
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function deletes(): bool
    {
        $deleted = false;

        // Find the given instance
        $entity = $this->createModel();
        $entities = $this->prepareQuery($entity)->get($entity->getKeyName());

        if ($entities->count() > 0) {
            foreach ($entities as $entity) {
                // Fire the deleted event
                $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.deleting', [$this, $entity]);

                // Delete the instance
                $deleted = $entity->delete();

                // Fire the deleted event
                $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.deleted', [$this, $entity]);
            }
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function delete($id): false|object
    {
        $deleted = false;

        // Find the given instance
        $entity = $id instanceof Model ? $id : $this->find($id);

        if ($entity) {
            // Fire the deleted event
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.deleting', [$this, $entity]);

            // Delete the instance
            $deleted = $entity->delete();

            // Fire the deleted event
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.deleted', [$this, $entity]);
        }

        return $deleted ? $entity : $deleted;
    }

    /**
     * {@inheritdoc}
     *
     * @param $id
     * @param string[] $attrs
     * @return object|null
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function find($id, $attrs = ['*']): ?object
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->find($id, $attrs)
        );
    }

    /////////////////////////         RESET WHERE CLAUSES          /////////////////////////

    /**
     * @inheritdoc
     */
    public function fullSearch($against, ...$matches): ?WhereClauseContract
    {
        return $this->whereRaw("MATCH ($matches) AGAINST (\\'$against\\' IN BOOLEAN MODE)");
    }

    /**
     * @inheritDoc
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function whereExistsExist(
        $attribute,
        $operator = null,
        $value = null,
        $existsColumn = '',
        string $boolean = 'and'
    ): bool
    {
        return $this->where($attribute, $operator, $value, $boolean)->exists($existsColumn);
    }

    /**
     * @param string $column
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function exists(string $column = '*'): bool
    {
        return (bool) $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->exists($column)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findOrFail($id, $attributes = ['*'])
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
     * @inheritDoc
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function findOrNew(int $id, array $attributes = ['*'], bool $sync_relations = false): ?object
    {
        if (null !== ($entity = $this->find($id, $attributes))) {
            return $entity;
        }

        return $this->create($attributes, $sync_relations);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function create(array $attrs = [], bool $sync_relations = false): ?object
    {
        // Create a new instance
        $entity = $this->createModel();

        // Fire the created event
        $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.creating', [$this, $entity]);

        // Extract relationships
        if ($sync_relations) {
            $relations = $this->extractRelations($entity, $attrs);
            Arr::forget($attrs, array_keys($relations));
        }

        // Fill instance with data
        $entity->fill($attrs);

        // Save the instance
        $created = $entity->save();

        // Sync relationships
        if ($sync_relations && isset($relations)) {
            $this->syncRelations($entity, $relations);
        }

        // Fire the created event
        $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.created', [$this, $entity]);

        // Return instance
        return $created ? $entity : null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findBy($attribute, $value, $attributes = ['*']): object|null
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->where($attribute, '=', $value)->first($attributes)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $attr
     * @return object|null
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findFirst($attr = ['*']): object|null
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->first($attr)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param null $perPage
     * @param string[] $attributes
     * @param string $pageName
     * @param null $page
     * @return LengthAwarePaginator
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function paginate(
        $perPage = null,
        $attributes = ['*'],
        $pageName = 'page',
        $page = null
    ): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            array_merge(func_get_args(), compact('page')),
            fn() => $this->prepareQuery($this->createModel())->paginate($perPage, $attributes, $pageName, $page)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param null $perPage
     * @param string[] $attributes
     * @param string $pageName
     * @param null $page
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function simplePaginate(
        $perPage = null,
        $attributes = ['*'],
        $pageName = 'page',
        $page = null
    ): \Illuminate\Contracts\Pagination\Paginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            array_merge(func_get_args(), compact('page')),
            fn() => $this->prepareQuery($this->createModel())->simplePaginate($perPage, $attributes, $pageName, $page)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param null $perPage
     * @param string[] $columns
     * @param string $cursorName
     * @param null $cursor
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function cursorPaginate($perPage = null, $columns = ['*'], $cursorName = 'cursor', $cursor = null)
    {
        $cursor = $cursor ?: Paginator::resolveCurrentPage($cursorName);

        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            array_merge(func_get_args(), compact('cursor')),
            fn() => $this->prepareQuery($this->createModel())->cursorPaginate($perPage, $columns, $cursorName, $cursor)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws JsonException
     */
    public function findWhere(array $where, $attrs = ['*'])
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($where, $attrs) {
                [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

                $this->where($attribute, $operator, $value, $boolean);

                return $this->prepareQuery($this->createModel())->get($attrs);
            }
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array $where
     * @param string[] $attrs
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findWhereIn(array $where, $attrs = ['*'])
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($where, $attrs) {
                [$attribute, $values, $boolean, $not] = array_pad($where, 4, null);

                $this->whereIn($attribute, $values, $boolean, $not);

                return $this->prepareQuery($this->createModel())->get($attrs);
            }
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array $where
     * @param string[] $attributes
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findWhereNotIn(array $where, $attributes = ['*'])
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($where, $attributes) {
                [$attribute, $values, $boolean] = array_pad($where, 3, null);

                $this->whereNotIn($attribute, $values, $boolean);

                return $this->prepareQuery($this->createModel())->get($attributes);
            }
        );
    }

    /**
     * @inheritdoc
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function findWhereHas(array $where, $attributes = ['*'])
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($where, $attributes) {
                [$relation, $callback, $operator, $count] = array_pad($where, 4, null);

                $this->whereHas($relation, $callback, $operator, $count);

                return $this->prepareQuery($this->createModel())->get($attributes);
            }
        );
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
    ): ?object
    {
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
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function update($id, array $attrs = [], bool $sync_relations = false): ?object
    {
        $updated = null;

        // Find the given instance
        $entity = $id instanceof Model ? $id : $this->find($id);

        if ($entity) {
            // Fire the updated event
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.updating', [$this, $entity]);

            // Extract relationships
            if ($sync_relations) {
                $relations = $this->extractRelations($entity, $attrs);
                Arr::forget($attrs, array_keys($relations));
            }

            // Fill instance with data
            $entity->fill($attrs);

            //Check if we are updating attributes values
            $dirty = $sync_relations ? [1] : $entity->getDirty();

            // Update the instance
            $updated = $entity->save();

            // Sync relationships
            if ($sync_relations && isset($relations)) {
                $this->syncRelations($entity, $relations);
            }

            if (count($dirty) > 0) {
                // Fire the updated event
                $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.updated', [$this, $entity]);
            }
        }

        return $updated ? $entity : $updated;
    }

    /**
     * @inheritdoc
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function store(int $id = null, array $attrs = [], bool $sync_relations = false): ?object
    {
        return !$id ? $this->create($attrs, $sync_relations) : $this->update($id, $attrs, $sync_relations);
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function updateOrInsert($attrs, $values = [], $sync_relations = false): ?object
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
     * @inheritDoc
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws RepositoryException
     */
    public function insert($values): bool
    {
        // Create a new instance
        $entity = $this->createModel();

        // Fire the created event
        $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.creating', [$this, $entity]);

        $inserted = $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->insert($values)
        );

        // Fire the created event
        $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.created', [$this, $entity]);

        return $inserted;
    }

    /**
     * {@inheritdoc}
     */
    public function restore($id)
    {
        $restored = false;

        // Find the given instance
        $entity = $id instanceof Model ? $id : $this->withTrashed()->find($id);

        if ($entity) {
            // Fire the restoring event
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.restoring', [$this, $entity]);

            // Restore the instance
            $restored = $entity->restore();

            // Fire the restored event
            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.restored', [$this, $entity]);
        }

        return $restored ? $entity : $restored;
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
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->getContainer('db')->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        $this->getContainer('db')->rollBack();
    }

    /**
     * {@inheritdoc}
     *
     * @param $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function min($column)
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->min($column)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function max($column)
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->max($column)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws JsonException
     */
    public function avg($column)
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            function () use ($column) {
                return $this->prepareQuery($this->createModel())->avg($column);
            }
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param $column
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function sum($column)
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->sum($column)
        );
    }

    /**
     * @inheritDoc
     *
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function deletesBy($where, array $values = []): ?bool
    {
        return $this->executeCallback(
            static::class,
            __FUNCTION__,
            func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->whereIn($where, $values)->delete()
        );
    }
}
