<?php

declare(strict_types=1);


namespace Nucleus\Repository\Criteries\OffsetLimit;


use Nucleus\Repository\Contracts\BaseCriteriaContract;
use Nucleus\Repository\Contracts\BaseRepositoryContract;

/**
 * Class LimitCriteria
 * @package Src\Criteries\OffsetLimit
 */
class LimitCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $limit;

    /**
     * @param $limit
     */
    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->limit($this->limit);
    }

}
