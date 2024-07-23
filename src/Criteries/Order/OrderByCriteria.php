<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\Order;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class OrderByCriteria
 *
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
     * @param string $order
     */
    public function __construct($column, $order = 'asc')
    {
        $this->column = $column;
        $this->order = $order;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        return $query->orderBy($this->column, $this->order);
    }
}
