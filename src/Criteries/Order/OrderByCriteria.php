<?php

declare(strict_types=1);


namespace Nucleus\Repository\Criteries\Order;


use Nucleus\Repository\Contracts\BaseCriteriaContract;
use Nucleus\Repository\Contracts\BaseRepositoryContract;

/**
 * Class OrderByCriteria
 * @package Src\Criteries\Order
 */
class OrderByCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $order;

    /**
     * @param $column
     * @param  string  $order
     */
    public function __construct($column, $order = 'asc')
    {
        $this->column = $column;
        $this->order = $order;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->orderBy($this->column, $this->order);
    }
}
