<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Exceptions\RepositoryException;

trait Store
{
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
                // Delete the instance
                $deleted = $entity->delete();
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
    public function delete(int|string $id): false|object
    {
        $deleted = false;

        // Find the given instance
        $entity = $id instanceof Model ? $id : $this->find($id);

        if ($entity) {
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
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function create(array $attrs = [], bool $sync_relations = false): ?object
    {
        // Create a new instance
        $entity = $this->createModel();

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
     * @throws RepositoryException
     */
    public function update(int|string $id, array $attrs = [], bool $sync_relations = false): ?object
    {
        $updated = null;

        // Find the given instance
        $entity = $id instanceof Model ? $id : $this->find($id);

        if ($entity) {
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

        $inserted = $this->executeCallback(static::class, __FUNCTION__, func_get_args(),
            fn() => $this->prepareQuery($this->createModel())->insert($values));

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
}
