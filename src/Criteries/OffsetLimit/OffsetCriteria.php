<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\OffsetLimit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class OffsetCriteria
 *
 * @package Src\Criteries\OffsetLimit
 */
class OffsetCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $offset;

    /**
     * @param $offset
     */
    public function __construct($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        return $query->offset($this->offset);
    }
}
