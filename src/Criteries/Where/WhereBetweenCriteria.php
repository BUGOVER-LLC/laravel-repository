<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\Where;

use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class WhereBetweenCriteria
 *
 * @package Src\Criteries\Where
 */
class WhereBetweenCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $to;

    /**
     * @var string
     */
    protected $column;

    /**
     * @param $column
     * @param $from
     * @param $to
     */
    public function __construct($column, $from, $to)
    {
        $this->column = $column;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository)
    {
        return $query->whereBetween($this->column, [$this->from, $this->to]);
    }
}
