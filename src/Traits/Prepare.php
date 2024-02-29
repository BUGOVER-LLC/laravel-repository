<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Prepare all clause queries
 */
trait Prepare
{
    /**
     * @param Model|Builder $model
     * @return Builder|Model
     */
    private function prepareQuery(Model|Builder $model): Builder|Model
    {
        // Add a basic where clause to the query current table
        $model = $this->prepareTable($model);

        // Add a basic whereJson clause to the query
        $model = $this->prepareDateQuery($model);

        // Prepare Relations has queries
        $model = $this->prepareRelationQuery($model);

        // Set the relationships that should be eager loaded
        $model = $this->prepareRelations($model);

        // Prepare Aggregation method to relation
        $model = $this->prepareRelationAgg($model);

        //Prepare common utils queries
        $model = $this->prepareCommonQuery($model);

        // Apply all criteria to the query
        if (method_exists($this, 'applyCriteria')) {
            $model = $this->applyCriteria($model, $this);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Model|Builder
     */
    private function prepareTable(Model|Builder $model): Model|Builder
    {
        foreach ($this->where as $where) {
            [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->where($attribute, $operator, $value, $boolean);
        }

        // Add a basic orWhere clause to the query
        foreach ($this->orWhere as $orWhere) {
            [$column, $operator, $value] = array_pad($orWhere, 3, null);

            $model = $model->orWhere($column, $operator, $value);
        }

        // Add a "where in" clause to the query
        foreach ($this->whereIn as $whereIn) {
            [$attribute, $values, $boolean, $not] = array_pad($whereIn, 4, null);

            $model = $model->whereIn($attribute, $values, $boolean, $not);
        }

        // Add a "where not in" clause to the query
        foreach ($this->whereNotIn as $whereNotIn) {
            [$attribute, $values, $boolean] = array_pad($whereNotIn, 3, null);

            $model = $model->whereNotIn($attribute, $values, $boolean);
        }
        // Add a basic whereJson clause to the query
        foreach ($this->whereJson as $where) {
            [$attribute, $value, $boolean, $not] = array_pad($where, 4, null);

            $model = $model->whereJsonContains($attribute, $value, $boolean, $not);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->orWhereJson as $where) {
            [$attribute, $value] = array_pad($where, 2, null);

            $model = $model->orWhereJsonContains($attribute, $value);
        }

        // Add a basic whereJsonCount clause to the query
        foreach ($this->whereJsonCount as $where) {
            [$attribute, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->whereJsonLength($attribute, $operator, $value, $boolean);
        }

        // Add a basic whereJsonCount clause to the query
        foreach ($this->whereJsonNotIn as $where) {
            [$attribute, $value, $boolean] = array_pad($where, 3, null);

            $model = $model->whereJsonDoesntContain($attribute, $value, $boolean);
        }

        foreach ($this->when as $when) {
            [$value, $callback, $default] = array_pad($when, 3, null);

            $model = $model->when($value, $callback, $default);
        }
        // Add a basic whereJson clause to the query
        foreach ($this->whereBetween as $where) {
            [$column, $values, $boolean, $not] = array_pad($where, 4, null);

            $model = $model->whereBetween($column, $values, $boolean, $not);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->orWhereBetween as $where) {
            [$column, $values] = array_pad($where, 2, null);

            $model = $model->orWhereBetween($column, $values);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->whereNotBetween as $where) {
            [$column, $values, $boolean] = array_pad($where, 3, null);

            $model = $model->whereNotBetween($column, $values, $boolean);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->whereExists as $where) {
            [$callback, $boolean, $not] = array_pad($where, 3, null);

            $model = $model->whereExists($callback, $boolean, $not);
        }

        foreach ($this->whereRaw as $whereRaw) {
            [$sql, $binding, $boolean] = array_pad($whereRaw, 3, null);

            $model = $model->whereRaw($sql, $binding, $boolean);
        }

        foreach ($this->orWhereRaw as $whereRaw) {
            [$sql, $binding, $boolean] = array_pad($whereRaw, 3, null);

            $model = $model->orWhereRaw($sql, $binding, $boolean);
        }

        foreach ($this->havingRaw as $havingRaw) {
            [$sql, $binding, $boolean] = array_pad($havingRaw, 3, null);

            $model = $model->havingRaw($sql, $binding, $boolean);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Builder|Model
     */
    private function prepareDateQuery(Model|Builder $model): Model|Builder
    {
        foreach ($this->whereDate as $where) {
            [$column, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->whereDate($column, $operator, $value, $boolean);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->whereMonth as $where) {
            [$column, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->whereMonth($column, $operator, $value, $boolean);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->whereDay as $where) {
            [$column, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->whereDay($column, $operator, $value, $boolean);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->whereTime as $where) {
            [$column, $operator, $value, $boolean] = array_pad($where, 4, null);

            $model = $model->whereTime($column, $operator, $value, $boolean);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Builder|Model
     */
    private function prepareRelationQuery(Model|Builder $model): Model|Builder
    {
        // Add a "where has" clause to the query
        foreach ($this->has as $has) {
            [$relation, $operator, $count, $boolean, $callback] = array_pad($has, 5, null);

            $model = $model->has($relation, $operator, $count, $boolean, $callback);
        }

        // Add a "where has" clause to the query
        foreach ($this->orHas as $orHas) {
            [$relation, $operator, $count] = array_pad($orHas, 3, null);

            $model = $model->orHas($relation, $operator, $count);
        }

        // Add a "where has" clause to the query
        foreach ($this->whereHas as $whereHas) {
            [$relation, $callback, $operator, $count] = array_pad($whereHas, 4, null);

            $model = $model->whereHas($relation, $callback, $operator, $count);
        }

        // Add a "where has" clause to the query
        foreach ($this->doesntHave as $doesntHave) {
            [$relation, $boolean, $callback] = array_pad($doesntHave, 3, null);

            $model = $model->doesntHave($relation, $boolean, $callback);
        }

        // Add a "where has" clause to the query
        foreach ($this->orDoesntHave as $orDoesntHave) {
            [$relation] = array_pad($orDoesntHave, 1, null);

            $model = $model->orDoesntHave($relation);
        }

        // Add a "where has" clause to the query
        foreach ($this->orWhereHas as $orWhereHas) {
            [$relation, $callback, $operator, $count] = array_pad($orWhereHas, 4, null);

            $model = $model->orWhereHas($relation, $callback, $operator, $count);
        }

        // Add a "where has" clause to the query
        foreach ($this->whereDoesntHave as $whereDoesntHave) {
            [$relation, $callback] = array_pad($whereDoesntHave, 2, null);

            $model = $model->whereDoesntHave($relation, $callback);
        }

        // Add a "where has" clause to the query
        foreach ($this->orWhereDoesntHave as $orWhereDoesntHave) {
            [$relation, $callback] = array_pad($orWhereDoesntHave, 2, null);

            $model = $model->orWhereDoesntHave($relation, $callback);
        }

        // Add a "where has" clause to the query
        foreach ($this->hasMorph as $hasMorph) {
            [$relation, $types, $operator, $count, $boolean, $callback] = array_pad($hasMorph, 6, null);

            $model = $model->hasMorph($relation, $types, $operator, $count, $boolean, $callback);
        }

        // Add a "where has" clause to the query
        foreach ($this->whereHasMorph as $whereHasMorph) {
            [$relation, $types, $callback, $operator, $count] = array_pad($whereHasMorph, 5, null);

            $model = $model->whereHasMorph($relation, $types, $callback, $operator, $count);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Builder|Model
     */
    private function prepareRelations(Model|Builder $model): Model|Builder
    {
        if (!empty($this->relations)) {
            $model = $model->with($this->relations);
        }

        // Set the relationships as join
        foreach ($this->join as $join) {
            [$table, $first, $operator, $second, $type, $where] = array_pad($join, 6, null);

            $model = $model->join($table, $first, $operator, $second, $type, $where);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Builder|Model
     */
    private function prepareRelationAgg(Model|Builder $model): Model|Builder
    {
        // Add a basic whereJson clause to the query
        if (!empty($this->withCount)) {
            $model = $model->withCount($this->withCount);
        }

        if (!empty($this->withExists)) {
            $model = $model->withExists($this->withExists);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->withSum as $with_sum) {
            [$relation, $field] = array_pad($with_sum, 2, null);

            $model = $model->withSum($relation, $field);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->withMax as $with_max) {
            [$relation, $field] = array_pad($with_max, 2, null);

            $model = $model->withMax($relation, $field);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->withMin as $with_min) {
            [$relation, $field] = array_pad($with_min, 2, null);

            $model = $model->withMin($relation, $field);
        }

        // Add a basic whereJson clause to the query
        foreach ($this->withAvg as $with_avg) {
            [$relation, $field] = array_pad($with_avg, 2, null);

            $model = $model->withAvg($relation, $field);
        }

        return $model;
    }

    /**
     * @param Builder|Model $model
     * @return Model|Builder
     */
    private function prepareCommonQuery(Model|Builder $model): Model|Builder
    {
        // Add a "scope" to the query
        foreach ($this->scopes as $scope => $parameters) {
            $model = $model->{$scope}(...$parameters);
        }

        // Add a "having" clause to the query
        foreach ($this->having as $having) {
            [$column, $operator, $value, $boolean] = array_pad($having, 4, null);

            $model = $model->having($column, $operator, $value, $boolean);
        }

        if ($this->withTrashed) {
            $model = $model->withTrashed();
        }

        if ($this->withoutScope) {
            $model = $model->withoutGlobalScopes();
        }

        foreach ($this->except as $except) {
            [$except] = array_pad($except, 1, null);

            $model = $model->except($except);
        }

        // Set the "offset" value of the query
        if ($this->offset > 0) {
            $model = $model->offset($this->offset);
        }

        // Set the "limit" value of the query
        if ($this->limit > 0) {
            $model = $model->limit($this->limit);
        }

        // Add an "group by" clause to the query.
        foreach ($this->groupBy as $group) {
            $model = $model->groupBy($group);
        }

        // Add an "order by" clause to the query.
        foreach ($this->orderBy as $orderBy) {
            [$attribute, $direction] = $orderBy;

            $model = $model->orderBy($attribute, $direction);
        }

        return $model;
    }
}
