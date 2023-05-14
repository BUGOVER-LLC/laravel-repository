<?php

declare(strict_types=1);


namespace Nucleus\Repository\Criteries\Where;


use Nucleus\Repository\Contracts\BaseCriteriaContract;
use Nucleus\Repository\Contracts\BaseRepositoryContract;

/**
 * Class WhereBetweenCriteria
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
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->whereBetween($this->column, [$this->from, $this->to]);
    }
}
