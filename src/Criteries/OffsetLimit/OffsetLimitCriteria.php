<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\OffsetLimit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class OffsetLimitCriteria
 *
 * @package Src\Criteries\OffsetLimit
 */
class OffsetLimitCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $offset;

    /**
     * @var string
     */
    protected $limit;

    /**
     * @param $offset
     * @param $limit
     */
    public function __construct($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        return $query->offset($this->offset)->limit($this->limit);
    }
}
