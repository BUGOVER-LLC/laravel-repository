<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

/**
 * Class BaseCriteria
 *
 * @package Repository
 */
interface BaseCriteriaContract
{
    /**
     * Apply current criterion to the given query and return query.
     *
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     *
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): mixed;
}
