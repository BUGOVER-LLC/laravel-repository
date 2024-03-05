<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\OffsetLimit;

use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class LimitCriteria
 *
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
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository)
    {
        return $query->limit($this->limit);
    }
}
