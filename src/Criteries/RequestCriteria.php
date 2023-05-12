<?php

declare(strict_types=1);

namespace Service\Repository\Criteries;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\BaseRepositoryContract;

use function in_array;
use function is_string;

/**
 * Class RequestCriteria
 * @package Service\Repository\Criteries
 */
class RequestCriteria implements BaseCriteriaContract
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * RequestCriteria constructor.
     * @param  Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply criteria in query repository
     *
     * @param  Builder|Model  $model
     * @param  BaseRepositoryContract  $repository
     *
     * @return Builder|Model
     * @throws Exception
     */
    public function apply($model, BaseRepositoryContract $repository): Model|Builder
    {
        $fields_searchable = $repository->getFieldsSearchable();
        $search = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $search_fields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $order_by = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sorted_by = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $with_count = $this->request->get(config('repository.criteria.params.withCount', 'withCount'), null);
        $search_join = $this->request->get(config('repository.criteria.params.searchJoin', 'searchJoin'), null);
        $sorted_by = !empty($sorted_by) ? $sorted_by : 'asc';

        if ($search && is_array($fields_searchable) && count($fields_searchable)) {
            $search_fields = is_array($search_fields) || null === $search_fields ? $search_fields : explode(';', $search_fields);
            $fields = $this->parserFieldsSearch($fields_searchable, $search_fields);
            $is_first_field = true;
            $search_data = $this->parserSearchData($search);
            $search = $this->parserSearchValue($search);
            $model_force_and_where = 'and' === strtolower($search_join);

            $model = $model->where(function ($query) use ($fields, $search, $search_data, $is_first_field, $model_force_and_where) {
                /** @var Builder $query */

                foreach ($fields as $field => $condition) {
                    if (is_numeric($field)) {
                        $field = $condition;
                        $condition = '=';
                    }

                    $value = null;

                    $condition = strtolower(trim($condition));

                    if (isset($search_data[$field])) {
                        $value = ('like' === $condition || 'ilike' === $condition) ? "%{$search_data[$field]}%" : $search_data[$field];
                    } elseif (null !== $search && !in_array($condition, ['in', 'between'])) {
                        $value = ('like' === $condition || 'ilike' === $condition) ? "%{$search}%" : $search;
                    }

                    $relation = null;
                    if (strpos($field, '.')) {
                        $explode = explode('.', $field);
                        $field = array_pop($explode);
                        $relation = implode('.', $explode);
                    }
                    if ('in' === $condition) {
                        $value = explode(',', $value);
                        if ('' === trim($value[0]) || $field == $value[0]) {
                            $value = null;
                        }
                    }
                    if ('between' === $condition) {
                        $value = explode(',', $value);
                        if (count($value) < 2) {
                            $value = null;
                        }
                    }
                    $model_table_name = $query->getModel()->getTable();
                    if ($is_first_field || $model_force_and_where) {
                        if (null !== $value) {
                            if (null === $relation) {
                                if ('in' === $condition) {
                                    $query->whereIn($model_table_name.'.'.$field, $value);
                                } elseif ('between' === $condition) {
                                    $query->whereBetween($model_table_name.'.'.$field, $value);
                                } else {
                                    $query->where($model_table_name.'.'.$field, $condition, $value);
                                }
                            } else {
                                $query->whereHas($relation, function ($query) use ($field, $condition, $value) {
                                    if ('in' === $condition) {
                                        $query->whereIn($field, $value);
                                    } elseif ('between' === $condition) {
                                        $query->whereBetween($field, $value);
                                    } else {
                                        $query->where($field, $condition, $value);
                                    }
                                });
                            }
                            $is_first_field = false;
                        }
                    } elseif (null !== $value) {
                        if (null === $relation) {
                            if ('in' === $condition) {
                                $query->orWhereIn($model_table_name.'.'.$field, $value);
                            } elseif ('between' === $condition) {
                                $query->whereBetween($model_table_name.'.'.$field, $value);
                            } else {
                                $query->orWhere($model_table_name.'.'.$field, $condition, $value);
                            }
                        } else {
                            $query->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                                if ('in' === $condition) {
                                    $query->whereIn($field, $value);
                                } elseif ('between' === $condition) {
                                    $query->whereBetween($field, $value);
                                } else {
                                    $query->where($field, $condition, $value);
                                }
                            });
                        }
                    }
                }
            });
        }

        if (isset($order_by) && !empty($order_by)) {
            $order_by_split = explode(';', $order_by);
            if (count($order_by_split) > 1) {
                $sorted_by_split = explode(';', $sorted_by);
                foreach ($order_by_split as $orderBySplitItemKey => $orderBySplitItem) {
                    $sorted_by = $sorted_by_split[$orderBySplitItemKey] ?? $sorted_by_split[0];
                    $model = $this->parserFieldsOrderBy($model, $orderBySplitItem, $sorted_by);
                }
            } else {
                $model = $this->parserFieldsOrderBy($model, $order_by_split[0], $sorted_by);
            }
        }

        if (isset($filter) && !empty($filter)) {
            if (is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $model = $model->select($filter);
        }

        if ($with) {
            $with = explode(';', $with);
            $model = $model->with($with);
        }

        if ($with_count) {
            $with_count = explode(';', $with_count);
            $model = $model->withCount($with_count);
        }

        return $model;
    }

    /**
     * @param  array  $fields
     * @param  array|null  $searchFields
     * @return array
     * @throws Exception
     */
    protected function parserFieldsSearch(array $fields = [], array $searchFields = null): array
    {
        if (null !== $searchFields && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=',
                'like'
            ]);
            $originalFields = $fields;
            $fields = [];

            foreach ($searchFields as $index => $field) {
                $field_parts = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields, true);

                if ((2 === count($field_parts)) && in_array($field_parts[1], $acceptedConditions, true)) {
                    unset($originalFields[$temporaryIndex]);
                    $field = $field_parts[0];
                    $condition = $field_parts[1];
                    $originalFields[$field] = $condition;
                    $searchFields[$index] = $field;
                }
            }

            foreach ($originalFields as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = '=';
                }
                if (in_array($field, $searchFields, true)) {
                    $fields[$field] = $condition;
                }
            }

            if (0 === count($fields)) {
                throw new RuntimeException(trans('repository::criteria.fields_not_accepted', ['field' => implode(',', $searchFields)]));
            }
        }

        return $fields;
    }

    /**
     * @param $search
     *
     * @return array
     */
    protected function parserSearchData($search): array
    {
        $searchData = [];

        if (strpos($search, ':')) {
            $fields = explode(';', $search);

            foreach ($fields as $row) {
                try {
                    [$field, $value] = explode(':', $row);
                    $searchData[$field] = $value;
                } catch (Exception) {
                    //Surround offset error
                }
            }
        }

        return $searchData;
    }

    /**
     * @param $search
     * @return string|null
     */
    protected function parserSearchValue($search): ?string
    {
        if (strpos($search, ';') || strpos($search, ':')) {
            $values = explode(';', $search);
            foreach ($values as $value) {
                $s = explode(':', $value);
                if (1 === count($s)) {
                    return $s[0];
                }
            }

            return null;
        }

        return $search;
    }

    /**
     * @param $model
     * @param $orderBy
     * @param $sortedBy
     * @return mixed
     */
    protected function parserFieldsOrderBy($model, $orderBy, $sortedBy): mixed
    {
        $split = explode('|', $orderBy);
        if (count($split) > 1) {
            $table = $model->getModel()->getTable();
            $sortTable = $split[0];
            $sortColumn = $split[1];

            $split = explode(':', $sortTable);
            $localKey = '.id';
            if (count($split) > 1) {
                $sortTable = $split[0];

                $commaExp = explode(',', $split[1]);
                $keyName = $table.'.'.$split[1];
                if (count($commaExp) > 1) {
                    $keyName = $table.'.'.$commaExp[0];
                    $localKey = '.'.$commaExp[1];
                }
            } else {
                /*
                 * products -> product_id
                 */
                $prefix = Str::singular($sortTable);
                $keyName = $table.'.'.$prefix.'_id';
            }

            $model = $model
                ->leftJoin($sortTable, $keyName, '=', $sortTable.$localKey)
                ->orderBy($sortColumn, $sortedBy)
                ->addSelect($table.'.*');
        } else {
            $model = $model->orderBy($orderBy, $sortedBy);
        }

        return $model;
    }
}
