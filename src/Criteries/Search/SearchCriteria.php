<?php

declare(strict_types=1);


namespace Service\Repository\Criteries\Search;


use Illuminate\Database\Eloquent\Builder;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\BaseRepositoryContract;

/**
 * Class SearchCriteria
 * @package Src\Criteries
 */
class SearchCriteria implements BaseCriteriaContract
{
    /**
     * @var
     */
    protected $search;

    /**
     * @var
     */
    protected $columns;

    /**
     * SearchCriteria constructor.
     * @param $search
     * @param $columns
     */
    public function __construct($search, $columns)
    {
        $this->search = $search;
        $this->columns = (array)$columns;
    }

    /**
     * @param  mixed  $query
     * @param  BaseRepositoryContract  $repository
     * @return mixed
     */
    public function apply($query, BaseRepositoryContract $repository)
    {
        $i = 0;

        foreach ($this->columns as $key => $column) {
            if (is_numeric($key)) {
                $q = $this->searchInModel($query, $column, $this->search, $i);
            } else {
                $q = $this->searchInRelation($query, $key, $column, $this->search, $i);
            }

            ++$i;
        }

        return $q;
    }

    /**
     * @param $model
     * @param $column
     * @param $search
     * @param $i
     * @return mixed
     */
    protected function searchInModel($model, $column, $search, $i)
    {
        if (0 === $i) {
            return $model->where($column, 'LIKE', '%'.$search.'%');
        }

        return $model->orWhere($column, 'LIKE', '%'.$search.'%');
    }

    /**
     * @param $model
     * @param $relation
     * @param $column
     * @param $search
     * @param $i
     * @return mixed
     */
    protected function searchInRelation($model, $relation, $column, $search, $i)
    {
        if (0 === $i) {
            return $model->whereHas(
                $relation,
                function (Builder $q) use ($search, $column) {
                    return $q->where($column, 'LIKE', '%'.$search.'%');
                }
            );
        }

        return $model->orWhereHas(
            $relation,
            function (Builder $q) use ($search, $column) {
                return $q->where($column, 'LIKE', '%'.$search.'%');
            }
        );
    }
}
