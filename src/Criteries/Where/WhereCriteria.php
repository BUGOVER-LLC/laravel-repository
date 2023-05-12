<?php

declare(strict_types=1);


namespace Service\Repository\Criteries\Where;


use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\BaseRepositoryContract;

/**
 * Class WhereCriteria
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
     * @param  string  $sign
     */
    public function __construct($attribute, $value, $sign = '=')
    {
        $this->attribute = $attribute;
        $this->value = $value;
        $this->sign = $sign;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->where($this->attribute, $this->sign, $this->value);
    }
}
