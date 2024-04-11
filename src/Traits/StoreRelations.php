<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Service\Repository\Exceptions\RepositoryException;

use function get_class;

trait StoreRelations
{
    /**
     * Extract relationships.
     *
     * @param object $entity
     * @param array $attributes
     *
     * @return array
     */
    private function extractRelations(object $entity, array $attributes): array
    {
        $relations = [];
        $potential = array_diff(array_keys($attributes), $entity->getFillable());

        array_walk(
            $potential, static function ($relation) use ($entity, $attributes, &$relations) {
            if (method_exists($entity, $relation)) {
                $relations[$relation] = [
                    'values' => $attributes[$relation],
                    'class' => get_class($entity->{$relation}()),
                ];
            }
        }
        );

        return $relations;
    }

    /**
     * Sync relationships.
     *
     * @param mixed $entity
     * @param array $relations
     * @param bool $detaching
     * @return void
     * @throws RepositoryException
     */
    private function syncRelations(object $entity, array $relations, bool $detaching = true): void
    {
        foreach ($relations as $method => $relation) {
            switch ($relation['class']) {
                case BelongsToMany::class:
                    $entity->{$method}()->sync((array)$relation['values'], $detaching);
                    break;
                case HasMany::class:
                    $entity->{$method}()->createMany(
                        array_is_list($relation['values']) ? $relation['values'] : [$relation['values']],
                        $detaching
                    );
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);
                    $rel_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                        $rel_repository->getRepositoryId() . '.entity.created', [$this, $relation['values']]
                    )) : null;
                    break;
                case HasOne::class:
                    $entity->{$method}()->create($relation['values'], $detaching);
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);
                    $rel_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                        $rel_repository->getRepositoryId() . '.entity.created', [$this, $relation['values']]
                    )) : null;
                    break;
                case BelongsTo::class:
                    $relation['values'] = array_merge(
                        [$entity->getKeyName() => $entity->{$entity->getKeyName()}],
                        $relation['values']
                    );
                    $entity->{$method}()->create($relation['values'], $detaching);
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);
                    $rel_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                        $rel_repository->getRepositoryId() . '.entity.created', [$this, $relation['values']]
                    )) : null;
                    break;
                default:
                    throw new RepositoryException('Error relation type ' . $relation['class'], 500);
            }
        }
    }

    /**
     * @param object $entity
     * @param string $method
     * @return object|string
     */
    private function getRelationRepositoryId(object $entity, string $method): object|string
    {
        $repository = $entity->{$method}()->getRelated()->getModelRepositoryClass();

        if ($repository) {
            return '';
        }

        return app($repository);
    }
}
