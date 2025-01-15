<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseCriteria
 *
 * @package Repository
 */
interface BaseCriteriaContract
{
    /**
     * Apply the current criterion to the given query and return a query.
     *
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     *
     * @return Model|Builder
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder;
}
