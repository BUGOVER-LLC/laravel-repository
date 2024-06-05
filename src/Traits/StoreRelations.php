<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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
            $potential,
            static function ($relation) use ($entity, $attributes, &$relations) {
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
     * @param string $event
     * @param bool $detaching
     * @return void
     * @throws RepositoryException
     */
    private function syncRelations(
        Model $entity,
        array $relations,
        string $event = 'create',
        bool $detaching = true
    ): void
    {
        $model_repository = $this->getRelationRepositoryId($entity);

        foreach ($relations as $method => $relation) {
            switch ($relation['class']) {
                case BelongsToMany::class:
                    $entity->{$method}()->sync((array) $relation['values'], $detaching);

                    $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                        $this->getRepositoryId() . '.entity.update',
                        [$this, $relation['values']]
                    )) : null;
                    break;
                case HasMany::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);

                    if ('update' === $event) {
                        $entity->{$method}()->update($relation['values']);
                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.update',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    } else {
                        $entity->{$method}()->createMany(
                            array_is_list($relation['values']) ? $relation['values'] : [$relation['values']],
                            $detaching
                        );
                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.created',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    }
                    break;
                case HasOne::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);

                    if ('update' === $event) {
                        $entity->{$method}()->update($relation['values'], $detaching);
                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.updated',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    } else {
                        $entity->{$method}()->create($relation['values'], $detaching);
                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.created',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    }
                    break;
                case BelongsTo::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);

                    if ('update' === $event) {
                        $entity->{$method}()->update($relation['values'], $detaching);

                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.updated',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    } else {
                        $values = array_merge(
                            [$entity->getKeyName() => $entity->{$entity->getKeyName()}],
                            $relation['values']
                        );
                        $entity->{$method}()->create($values, $detaching);

                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.created',
                            [$this, $rel_repository, $values]
                        )) : null;
                    }
                    break;
                case MorphOne::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);

                    if ('updated' === $event) {
                        $entity->{$method}()->update($relation['values'], $detaching);

                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.updated',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    } else {
                        $entity->{$method}()->create($relation['values'], $detaching);
                        $model_repository ? DB::afterCommit(fn() => $this->getContainer('events')->dispatch(
                            $this->getRepositoryId() . '.entity.created',
                            [$this, $rel_repository, $relation['values']]
                        )) : null;
                    }
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
    private function getRelationRepositoryId(object $entity, string $method = ''): object|string
    {
        $repository = $method ? $entity->{$method}()->getRelated()->getModelRepositoryClass(
        ) : $entity->getModelRepositoryClass();
        if (!$repository) {
            return '';
        }

        return app($repository);
    }
}
