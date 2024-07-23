<?php

declare(strict_types=1);

namespace Service\Repository\Criteries\Where;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\EloquentRepositoryContract;

/**
 * Class WhereCriteria
 *
 * @package Src\Criteries\Where
 */
class WhereCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $sign;

    /**
     * @param $attribute
     * @param $value
     * @param string $sign
     */
    public function __construct($attribute, $value, $sign = '=')
    {
        $this->attribute = $attribute;
        $this->value = $value;
        $this->sign = $sign;
    }

    /**
     * @param mixed $query
     * @param EloquentRepositoryContract $repository
     * @return mixed
     */
    public function apply($query, EloquentRepositoryContract $repository): Model|Builder
    {
        return $query->where($this->attribute, $this->sign, $this->value);
    }
}
