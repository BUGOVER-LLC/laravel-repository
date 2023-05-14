<?php

declare(strict_types=1);


namespace Nucleus\Repository\Criteries\GroupBy;


use Nucleus\Repository\Contracts\BaseCriteriaContract;
use Nucleus\Repository\Contracts\BaseRepositoryContract;

use function is_array;

/**
 * Class GroupByArrayCriteria
 * @package Src\Criteries\GroupBy
 */
class GroupByArrayCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $columns;

    /**
     * @param  array  $columns
     */
    public function __construct($columns = [])
    {
        if (!is_array($columns)) {
            $columns = [
                $columns
            ];
        }

        $this->columns = $columns;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        $q = null;

        foreach ((array)$this->columns as $column) {
            $q = $query->groupBy($column);
        }

        return $q;
    }
}
