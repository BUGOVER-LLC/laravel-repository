<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Service\Repository\Exceptions\RepositoryException;

trait RelationsStore
{
    /**
     * Extract relationships.
     *
     * @param object $entity
     * @param array $attributes
     *
     * @return array
     */
    protected function extractRelations(object $entity, array $attributes): array
    {
        $relations = [];
        $potential = array_diff(array_keys($attributes), $entity->getFillable());

        array_walk(
            $potential,
            static function ($relation) use ($entity, $attributes, &$relations) {
                if (method_exists($entity, $relation)) {
                    $relations[$relation] = [
                        'values' => $attributes[$relation],
                        'class' => \get_class($entity->{$relation}()),
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
    protected function syncRelations(object $entity, array $relations, bool $detaching = true): void
    {
        foreach ($relations as $method => $relation) {
            switch ($relation['class']) {
                case BelongsToMany::class:
                    $entity->{$method}()->sync((array)$relation['values'], $detaching);
                    break;
                case HasMany::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);
                    $entity->{$method}()->createMany(array_is_list($relation['values']) ? $relation['values'] : [$relation['values']],
                        $detaching);
                    $this->getContainer('events')->dispatch(
                        $rel_repository->getRepositoryId() . '.entity.created',
                        [$this, $relation['values']]
                    );
                    break;
                case HasOne::class:
                    $rel_repository = $this->getRelationRepositoryId($entity, $method);
                    $entity->{$method}()->create($relation['values'], $detaching);
                    $this->getContainer('events')->dispatch(
                        $rel_repository->getRepositoryId() . '.entity.created',
                        [$this, $relation['values']]
                    );
                    break;
                default:
                    throw new RepositoryException('Error relation type ' . $relation['class'], 500);
            }
        }
    }

    /**
     * @param $entity
     * @param $method
     * @return mixed
     */
    protected function getRelationRepositoryId($entity, $method): mixed
    {
        return app($entity->{$method}()->getRelated()->getModelRepositoryClass());
    }
}
