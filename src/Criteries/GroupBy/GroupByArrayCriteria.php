<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\GroupBy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

use function is_array;

/**
 * Class GroupByArrayCriteria
 *
 * @package Src\Criteries\GroupBy
 */
class GroupByArrayCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $columns;

    /**
     * @param array $columns
     */
    public function __construct($columns = [])
    {
        if (!is_array($columns)) {
            $columns = [
                $columns,
            ];
        }

        $this->columns = $columns;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        $q = null;

        foreach ((array) $this->columns as $column) {
            $q = $query->groupBy($column);
        }

        return $q;
    }
}
