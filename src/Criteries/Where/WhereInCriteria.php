<?php

declare(strict_types=1);


namespace Service\Repository\Criteries\Where;


use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\BaseRepositoryContract;

use function is_array;

/**
 * Class WhereInCriteria
 * @package Src\Criteries\Where
 */
class WhereInCriteria implements BaseCriteriaContract
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $values;

    /**
     * @param $attribute
     * @param $values
     */
    public function __construct($attribute, $values)
    {
        if (!is_array($values)) {
            $values = [
                $values
            ];
        }

        $this->attribute = $attribute;
        $this->values = $values;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        return $query->whereIn($this->attribute, $this->values);
    }
}
