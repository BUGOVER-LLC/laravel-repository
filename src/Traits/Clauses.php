<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Closure;
use Service\Repository\Contracts\WhereClauseContract;

/**
 * Class Clauses
 * @package Repository
 */
trait Clauses
{
    /**
     * The relations to eager load on query execution.
     *
     * @var array
     */
    protected array $relations = [];
    /**
     * The relations to eager load on query execution.
     *
     * @var array
     */
    protected array $join = [];
    /**
     * The query where clauses.
     *
     * @var array
     */
    protected array $where = [];
    /**
     * The query where clauses.
     *
     * @var array
     */
    protected array $orWhere = [];
    /**
     * The query whereIn clauses.
     *
     * @var array
     */
    protected array $whereIn = [];
    /**
     * The query whereNotIn clauses.
     *
     * @var array
     */
    protected array $whereNotIn = [];
    /**
     * The query whereHas clauses.
     *
     * @var array
     */
    protected array $whereHas = [];
    /**
     * The query scopes.
     *
     * @var array
     */
    protected array $scopes = [];
    /**
     * The column to order results by.
     *
     * @var array
     */
    protected array $orderBy = [];
    /**
     * The column to order results by.
     *
     * @var array
     */
    protected array $groupBy = [];
    /**
     * The query having clauses.
     *
     * @var array
     */
    protected array $having = [];
    /**
     * @var array
     */
    protected array $whereJson = [];
    /**
     * @var array
     */
    protected array $whereExists = [];
    /**
     * @var array
     */
    protected array $whereJsonNotIn = [];
    /**
     * @var array
     */
    protected array $orWhereJson = [];
    /**
     * @var array
     */
    protected array $whereJsonCount = [];
    /**
     * @var array
     */
    protected array $when = [];
    /**
     * @var array
     */
    protected array $has = [];
    /**
     * @var array
     */
    protected array $orHas = [];
    /**
     * @var array
     */
    protected array $orWhereHas = [];
    /**
     * @var array
     */
    protected array $whereDoesntHave = [];
    /**
     * @var array
     */
    protected array $orWhereDoesntHave = [];
    /**
     * @var array
     */
    protected array $hasMorph = [];
    /**
     * @var array
     */
    protected array $whereHasMorph = [];
    /**
     * @var array
     */
    protected array $whereBetween = [];
    /**
     * @var array
     */
    protected array $orWhereBetween = [];
    /**
     * @var array
     */
    protected array $whereNotBetween = [];
    /**
     * @var array
     */
    protected array $whereDate = [];
    /**
     * @var array
     */
    protected array $whereMonth = [];
    /**
     * @var array
     */
    protected array $whereDay = [];
    /**
     * @var array
     */
    protected array $whereTime = [];
    /**
     * @var array
     */
    protected array $withCount = [];
    /**
     * @var array
     */
    protected array $withSum = [];
    /**
     * @var array
     */
    protected array $withAvg = [];
    /**
     * @var array
     */
    protected array $withMax = [];
    /**
     * @var array
     */
    protected array $withMin = [];
    /**
     * @var array
     */
    protected array $withExists = [];
    /**
     * @var array
     */
    protected array $doesntHave = [];
    /**
     * @var array
     */
    protected array $orDoesntHave = [];
    /**
     * @var array
     */
    protected array $whereRaw = [];
    /**
     * @var array
     */
    protected array $orWhereRaw = [];
    /**
     * @var array
     */
    protected array $havingRaw = [];
    /**
     * @var array
     */
    protected array $except = [];
    /**
     * @var bool
     */
    protected bool $withTrashed = false;
    /**
     * @var bool
     */
    protected bool $withoutScope = false;
    /**
     * The "offset" value of the query.
     *
     * @var int|string|null
     */
    protected null|int|string $offset = null;
    /**
     * The "limit" value of the query.
     *
     * @var int|string|null
     */
    protected null|int|string $limit = null;

    /**
     * @inheritdoc
     */
    public function join($table, $first, $operator = null, $second = null, string $type = 'inner', $where = false): WhereClauseContract
    {
        $this->join[] = [$table, $first, $operator, $second, $type, $where];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn($attr, $values, $boolean = 'and', $not = false): WhereClauseContract
    {
        // The last `$boolean` & `$not` expressions are intentional to fix list() & array_pad() results
        $this->whereIn[] = [$attr, $values, $boolean ?: 'and', (bool)$not];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereNotIn($attr, $values, $boolean = 'and'): WhereClauseContract
    {
        // The last `$boolean` expression is intentional to fix list() & array_pad() results
        $this->whereNotIn[] = [$attr, $values, $boolean ?: 'and'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereHas($rel, Closure $callback = null, $operator = '>=', $count = 1): WhereClauseContract
    {
        // The last `$operator` & `$count` expressions are intentional to fix list() & array_pad() results
        $this->whereHas[] = [$rel, $callback, $operator ?: '>=', $count ?: 1];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($attr, $direction = 'asc')
    {
        $this->orderBy[] = [$attr, $direction ?: 'asc'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($column): static
    {
        $this->groupBy = array_merge((array)$this->groupBy, \is_array($column) ? $column : [$column]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * {@inheritdoc}
     */
    public function having($column, $operator = null, $value = null, string $boolean = 'and')
    {
        $this->having[] = [$column, $operator, $value, $boolean ?: 'and'];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereJsonLength(string $where, $attributes, $operator = '=', $boolean = 'and'): WhereClauseContract
    {
        $this->whereJsonCount[] = [$where, $operator ?: '=', $attributes, $boolean ?: 'and'];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereJsonContains(string $where, $attributes, string $boolean = 'and', bool $not = false): WhereClauseContract
    {
        $this->whereJson[] = [$where, $attributes, $boolean ?: 'and', $not ?: false];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhereJsonContains(string $where, $attributes): WhereClauseContract
    {
        $this->orWhereJson[] = [$where, $attributes];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereJsonNotIn(string $where, $attributes, string $boolean = 'and'): WhereClauseContract
    {
        $this->whereJsonNotIn[] = [$where, $attributes, $boolean ?: 'and'];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function when($value, $callback, $default = null): WhereClauseContract
    {
        $this->when[] = [$value, $callback, $default];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null): WhereClauseContract
    {
        $this->has[] = [$relation, $operator, $count, $boolean, $callback];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orHas($relation, $operator = '>=', $count = 1): WhereClauseContract
    {
        $this->orHas[] = [$relation, $operator, $count];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhereHas($relation, $callback = null, $operator = '>=', $count = 1): WhereClauseContract
    {
        $this->orWhereHas[] = [$relation, $callback, $operator, $count];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', $callback = null): WhereClauseContract
    {
        $this->hasMorph[] = [$relation, $types, $operator, $count, $boolean, $callback];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1): WhereClauseContract
    {
        $this->whereHasMorph[] = [$relation, $types, $callback, $operator, $count];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereBetween($column, $values, $boolean = 'and', $not = false): WhereClauseContract
    {
        $this->whereBetween[] = [$column, $values, $boolean, $not];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhereBetween($column, $values): WhereClauseContract
    {
        $this->orWhereBetween[] = [$column, $values];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereNotBetween($column, $values, $boolean = 'and'): WhereClauseContract
    {
        $this->whereNotBetween[] = [$column, $values, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and'): WhereClauseContract
    {
        $this->whereDate[] = [$column, $operator, $value, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and'): WhereClauseContract
    {
        $this->whereMonth[] = [$column, $operator, $value, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and'): WhereClauseContract
    {
        $this->whereDay[] = [$column, $operator, $value, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereTime($column, $operator, $value = null, $boolean = 'and'): WhereClauseContract
    {
        $this->whereTime[] = [$column, $operator, $value, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function diffDates(string $first, string $second, string $operator = '<=', int $count = 0): WhereClauseContract
    {
        $this->whereRaw("DATEDIFF($first,$second) $operator $count");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and'): WhereClauseContract
    {
        $this->whereRaw[] = [$sql, $bindings, $boolean];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereRaw($sql, $bindings = [], $boolean = 'and'): WhereClauseContract
    {
        $this->orWhereRaw[] = [$sql, $bindings, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function diffTimes(string $first, string $second, string $operator = '<=', int $count = 0): WhereClauseContract
    {
        $this->whereRaw("TIMEDIFF($first,$second) $operator $count");

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function diffTimestamps(string $first, string $second, string $operator = '<=', int $count = 0): WhereClauseContract
    {
        $this->whereRaw("TIMESTAMPDIFF($first,$second) $operator $count");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function havingRaw($sql, $bindings = [], $boolean = 'and'): WhereClauseContract
    {
        $this->havingRaw[] = [$sql, $bindings, $boolean];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere($column, $operator = null, $value = null): WhereClauseContract
    {
        $this->orWhere[] = [$column, $operator, $value];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($attr, $operator = null, $value = null, $boolean = 'and')
    {
        // The last `$boolean` expression is intentional to fix list() & array_pad() results
        $this->where[] = [$attr, $operator, $value, $boolean ?: 'and'];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereExists($callback, $boolean = 'and', $not = false): WhereClauseContract
    {
        $this->whereExists[] = [$callback, $boolean, $not];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function doesntHave($rel, $boolean = 'and', $callback = null): WhereClauseContract
    {
        $this->doesntHave[] = [$rel, $boolean, $callback];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orDoesntHave($rel): WhereClauseContract
    {
        $this->orDoesntHave[] = [$rel];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereDoesntHave($rel, $callback = null): WhereClauseContract
    {
        $this->whereDoesntHave[] = [$rel, $callback];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orWhereDoesntHave($rel, $callback = null): WhereClauseContract
    {
        $this->orWhereDoesntHave[] = [$rel, $callback];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function except(array ...$values): WhereClauseContract
    {
        $this->except[] = \is_array($values) ? $values : [$values];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function with($rels): WhereClauseContract
    {
        if (\is_string($rels)) {
            $rels = \func_get_args();
        }

        $this->relations = $rels;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withCount($relations)
    {
        if (\is_string($relations)) {
            $relations = \func_get_args();
        }

        $this->withCount = $relations;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withExists($relation)
    {
        if (\is_string($relation)) {
            $relation = \func_get_args();
        }

        $this->withExists = $relation;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withSum($relation, $field)
    {
        $this->withSum[] = [$relation, $field];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAvg($relation, $field)
    {
        $this->withAvg[] = [$relation, $field];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withMax($relation, $field)
    {
        $this->withMax[] = [$relation, $field];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withMin($relation, $field)
    {
        $this->withMin[] = [$relation, $field];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withTrashed(): WhereClauseContract
    {
        $this->withTrashed = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function scope($name, array $parameters = []): WhereClauseContract
    {
        $this->scopes[$name] = $parameters;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutScope()
    {
        $this->withoutScope = true;

        return $this;
    }
}
