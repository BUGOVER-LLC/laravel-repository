<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

use Closure;

/**
 *
 */
interface WhereClauseContract
{

    /**
     * @param  string  $where
     * @param $attributes
     * @param $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereJsonContains(string $where, $attributes, string $boolean = 'and', bool $not = false): WhereClauseContract;

    /**
     * @param  string  $where
     * @param $attributes
     * @return static
     */
    public function orWhereJsonContains(string $where, $attributes): WhereClauseContract;

    /**
     * @param  string  $where
     * @param $attributes
     * @param $operator
     * @param  string  $boolean
     * @return static
     */
    public function whereJsonLength(string $where, $attributes, $operator, $boolean = 'and'): WhereClauseContract;

    /**
     * @param  string  $where
     * @param $attributes
     * @param  string  $boolean
     * @return mixed
     */
    public function whereJsonNotIn(string $where, $attributes, string $boolean = 'and');

    /**
     * @param $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  null  $callback
     * @return static
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null): WhereClauseContract;

    /**
     * @param $relation
     * @param  string  $operator
     * @param  int  $count
     * @return WhereClauseContract
     */
    public function orHas($relation, $operator = '>=', $count = 1): WhereClauseContract;

    /**
     * @param $value
     * @param $callback
     * @param  string  $default
     * @return static
     */
    public function when($value, $callback, $default = null): WhereClauseContract;

    /**
     * @param $relation
     * @param  null  $callback
     * @param  string  $operator
     * @param  int  $count
     * @return static
     */
    public function orWhereHas($relation, $callback = null, $operator = '>=', $count = 1): WhereClauseContract;

    /**
     * @param $relation
     * @param $types
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  null  $callback
     * @return static
     */
    public function hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', $callback = null): WhereClauseContract;

    /**
     * @param $relation
     * @param $types
     * @param  null  $callback
     * @param  string  $operator
     * @param  int  $count
     * @return $this
     */
    public function whereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1): self;

    /**
     * @param $column
     * @param $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereBetween($column, $values, $boolean = 'and', $not = false);

    /**
     * @param $column
     * @param $values
     * @return static
     */
    public function orWhereBetween($column, $values);

    /**
     * @param $column
     * @param $values
     * @param  string  $boolean
     * @return static
     */
    public function whereNotBetween($column, $values, $boolean = 'and');

    /**
     * @param $column
     * @param $operator
     * @param  null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and');

    /**
     * @param $column
     * @param $operator
     * @param  null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and');

    /**
     * @param $column
     * @param $operator
     * @param  null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and');

    /**
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @param  int  $count
     * @return static
     */
    public function diffDates(string $first, string $second, string $operator = '<', int $count = 0);

    /**
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @param  int  $count
     * @return static
     */
    public function diffTimes(string $first, string $second, string $operator = '<', int $count = 0);

    /**
     * @param  string  $first
     * @param  string  $second
     * @param  string  $operator
     * @param  int  $count
     * @return static
     */
    public function diffTimestamps(string $first, string $second, string $operator = '<', int $count = 0);

    /**
     * @param $column
     * @param  null  $operator
     * @param  null  $value
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null);

    /**
     * @param $relations
     * @return static
     */
    public function withCount($relations);

    /**
     * @param $relation
     * @return mixed
     */
    public function withExists($relation);

    /**
     * @param $relation
     * @param $field
     * @return static
     */
    public function withSum($relation, $field);

    /**
     * @param $relation
     * @param $field
     * @return static
     */
    public function withAvg($relation, $field);

    /**
     * @param $relation
     * @param $field
     * @return mixed
     */
    public function withMax($relation, $field);

    /***
     * @param $relation
     * @param $field
     * @return mixed
     */
    public function withMin($relation, $field);

    /**
     * @return static
     */
    public function withTrashed(): WhereClauseContract;

    /**
     * @param $attribute
     * @param  null  $operator
     * @param  null  $value
     * @param  string  $existsColumn
     * @param  string  $boolean
     * @return bool
     */
    public function whereExistsExist($attribute, $operator = null, $value = null, string $existsColumn = '', string $boolean = 'and'): bool;

    /**
     * @param $callback
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereExists($callback, $boolean = 'and', $not = false);

    /**
     * @param $column
     * @param $operator
     * @param  null  $value
     * @param  string  $boolean
     * @return static
     */
    public function whereTime($column, $operator, $value = null, $boolean = 'and');

    /**
     * @param $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return static
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and');

    /**
     * @param $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return mixed
     */
    public function orWhereRaw($sql, $bindings = [], $boolean = 'and');

    /**
     * @param $sql
     * @param  array  $bindings
     * @param  string  $boolean
     * @return static
     */
    public function havingRaw($sql, $bindings = [], $boolean = 'and');

    /**
     * @param $rel
     * @param  string  $boolean
     * @param  null  $callback
     * @return static
     */
    public function doesntHave($rel, $boolean = 'and', $callback = null);

    /**
     * @param $rel
     * @return static
     */
    public function orDoesntHave($rel);

    /**
     * @param $rel
     * @param  null  $callback
     * @return static
     */
    public function whereDoesntHave($rel, $callback = null);

    /**
     * @param $rel
     * @param  null  $callback
     * @return static
     */
    public function orWhereDoesntHave($rel, $callback = null);

    /**
     * @param  array  ...$values
     * @return static
     */
    public function except(array ...$values);

    /**
     * @param $table
     * @param $first
     * @param  null  $operator
     * @param  null  $second
     * @param  string  $type
     * @param  false  $where
     * @return static
     */
    public function join($table, $first, $operator = null, $second = null, string $type = 'inner', $where = false): WhereClauseContract;

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|Callback  $attr
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     *
     * @return static
     */
    public function where($attr, $operator = null, $value = null, $boolean = 'and');

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string  $attr
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     *
     * @return static
     */
    public function whereIn($attr, $values, $boolean = 'and', $not = false): WhereClauseContract;

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string  $attr
     * @param  mixed  $values
     * @param  string  $boolean
     *
     * @return static
     */
    public function whereNotIn($attr, $values, $boolean = 'and'): WhereClauseContract;

    /**
     * Add a "where has relationship" clause to the query.
     *
     * @param  string  $rel
     * @param  Closure  $callback
     * @param  string  $operator
     * @param  int  $count
     *
     * @return static
     */
    public function whereHas($rel, Closure $callback = null, $operator = '>=', $count = 1): WhereClauseContract;

    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $offset
     *
     * @return static
     */
    public function offset($offset);

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $limit
     *
     * @return static
     */
    public function limit($limit);

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $attr
     * @param  string  $direction
     *
     * @return static
     */
    public function orderBy($attr, $direction = 'asc');

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  $column
     *
     * @return static
     */
    public function groupBy($column): static;

    /**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $boolean
     *
     * @return static
     */
    public function having($column, $operator = null, $value = null, string $boolean = 'and');

    /**
     * Add a "or having" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     *
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null);

    /**
     * Add a scope to the query.
     *
     * @param  string  $name
     * @param  array  $parameters
     *
     * @return static
     */
    public function scope($name, array $parameters = []): WhereClauseContract;

    /**
     * @return static
     */
    public function withoutScope();
}
