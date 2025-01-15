<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\GroupBy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class GroupByCriteria
 *
 * @package Src\Criteries\GroupBy
 */
class GroupByCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $column;

    /**
     * @param $column
     */
    public function __construct($column)
    {
        $this->column = $column;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return Model|Builder
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        return $query->groupBy($this->column);
    }
}
