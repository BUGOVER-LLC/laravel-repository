<?php

declare(strict_types=1);


namespace Nucleus\Repository\Criteries\GroupBy;


use Nucleus\Repository\Contracts\BaseCriteriaContract;
use Nucleus\Repository\Contracts\BaseRepositoryContract;

/**
 * Class GroupByCriteria
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
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->groupBy($this->column);
    }
}
