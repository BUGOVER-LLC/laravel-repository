<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use Service\Repository\Contracts\BaseCriteriaContract;
use Service\Repository\Contracts\BaseRepositoryContract;
use Service\Repository\Exceptions\CriteriaException;
use Service\Repository\Exceptions\RepositoryException;

use function call_user_func_array;
use function count;
use function get_class;
use function is_array;
use function is_object;
use function is_string;

/**
 * Trait Criteria
 * @package Service\Repository\Traits
 */
trait Criteria
{
    /**
     * List of repository criteria.
     *
     * @var array
     */
    protected array $criteria = [];

    /**
     * List of default repository criteria.
     *
     * @var array
     */
    protected array $defaultCriteria = [];

    /**
     * Skip criteria flag.
     * If setted to true criteria will not be apply to the query.
     *
     * @var bool
     */
    protected bool $skipCriteria = false;

    /**
     * Skip default criteria flag.
     * If setted to true default criteria will not be added to the criteria list.
     *
     * @var bool
     */
    protected bool $skipDefaultCriteria = false;

    /**
     * Push criterion to the criteria list.
     *
     * @param BaseCriteriaContract|Closure|array|string $criterion
     *
     * @return $this
     * @throws CriteriaException
     * @throws RepositoryException
     */
    public function pushCriterion($criterion): static
    {
        $this->addCriterion($criterion, 'criteria');

        return $this;
    }

    /**
     * Add criterion to the specific list.
     * low-level implementation of adding criterion to the list.
     *
     * @param Closure|BaseCriteriaContract|array|string $criterion
     * @param string $list
     *
     * @return $this
     * @throws RepositoryException
     *
     * @throws CriteriaException
     */
    protected function addCriterion($criterion, $list): static
    {
        if (!property_exists($this, $list)) {
            throw RepositoryException::listNotFound($list, $this);
        }

        if (!$criterion instanceof Closure &&
            !$criterion instanceof BaseCriteriaContract &&
            !is_string($criterion) &&
            !is_array($criterion)
        ) {
            throw CriteriaException::wrongCriterionType($criterion);
        }

        //If criterion is a string we will assume it is a class name without arguments
        //and we need to normalize signature for instantiation try
        if (is_string($criterion)) {
            $criterion = [$criterion, []];
        }

        //If the criterion is an array we will assume it is an array of class name with arguments
        //and try to instantiate this
        if (is_array($criterion)) {
            $criterion = call_user_func_array([$this, 'instantiateCriterion'],
                $this->extractCriterionClassAndArgs($criterion));
        }

        $this->{$list}[$this->getCriterionName($criterion)] = $criterion;

        return $this;
    }

    /**
     * Return class and arguments from passed array criterion.
     * Extracting class and arguments from array.
     *
     * @param array $criterion
     *
     * @return array
     * @throws CriteriaException
     *
     */
    protected function extractCriterionClassAndArgs(array $criterion): array
    {
        if (empty($criterion) || count($criterion) > 2) {
            throw CriteriaException::wrongArraySignature($criterion);
        }

        // If an array is assoc we assume that the key is a class and value is an arguments
        if (Arr::isAssoc($criterion)) {
            $criterion = [array_keys($criterion)[0], array_values($criterion)[0]];
        } elseif (1 === count($criterion)) {
            // If an array is not assoc but count is one, we can assume there is a class without arguments.
            // Like when a string passed as criterion
            $criterion[] = [];
        }

        return $criterion;
    }

    /**
     * Return name for the criterion.
     * If as criterion in parameter passed string we assume that is criterion class name.
     *
     * @param BaseCriteriaContract|Closure|string $criteria
     *
     * @return string
     */
    public function getCriterionName($criteria): string
    {
        if ($criteria instanceof Closure) {
            return spl_object_hash($criteria);
        }

        return is_object($criteria) ? get_class($criteria) : $criteria;
    }

    /**
     * Remove provided criteria from criteria list.
     *
     * @param array $criteria
     *
     * @return Criteria
     */
    public function removeCriteria(array $criteria): static
    {
        array_walk(
            $criteria,
            function ($criterion) {
                $this->removeCriterion($criterion);
            }
        );

        return $this;
    }

    /**
     * Remove provided criterion from criteria list.
     *
     * @param BaseCriteriaContract|Closure|string $criterion
     *
     * @return $this
     */
    public function removeCriterion($criterion): static
    {
        unset($this->criteria[$this->getCriterionName($criterion)]);

        return $this;
    }

    /**
     * Push array of criteria to the criteria list.
     *
     * @param array $criteria
     *
     * @return $this
     * @throws CriteriaException
     * @throws RepositoryException
     */
    public function pushCriteria(array $criteria): static
    {
        $this->addCriteria($criteria, 'criteria');

        return $this;
    }

    /**
     * Add criteria to the specific list
     * low-level implementation of adding criteria to the list.
     *
     * @param array $criteria
     * @param $list
     * @throws CriteriaException
     * @throws RepositoryException
     */
    protected function addCriteria(array $criteria, $list): void
    {
        array_walk(
            $criteria,
            function ($value, $key) use ($list) {
                $criterion = is_string($key) ? [$key, $value] : $value;
                $this->addCriterion($criterion, $list);
            }
        );
    }

    /**
     * Flush criteria list.
     * We can flush criteria only when they is not skipped.
     *
     * @return $this
     */
    public function flushCriteria(): static
    {
        if (!$this->skipCriteria) {
            $this->criteria = [];
        }

        return $this;
    }

    /**
     * Set skipCriteria flag.
     *
     * @param bool|true $flag
     *
     * @return $this
     */
    public function skipCriteria($flag = true): static
    {
        $this->skipCriteria = $flag;

        return $this;
    }

    /**
     * Set skipDefaultCriteria flag.
     *
     * @param bool|true $flag
     *
     * @return $this
     */
    public function skipDefaultCriteria(bool $flag = true): static
    {
        $this->skipDefaultCriteria = $flag;

        return $this;
    }

    /**
     * Return criterion object or closure from criteria list by name.
     *
     * @param $criterion
     *
     * @return BaseCriteriaContract|Closure|null
     */
    #[Pure] public function getCriterion($criterion): Closure|BaseCriteriaContract|null
    {
        if (!$this->hasCriterion($criterion)) {
            return null;
        }

        return $this->getCriteria()[$this->getCriterionName($criterion)];
    }

    /**
     * Check if a given criterion name now in the criteria list.
     *
     * @param BaseCriteriaContract|Closure|string $criterion
     *
     * @return bool
     */
    #[Pure] public function hasCriterion($criterion): bool
    {
        return isset($this->getCriteria()[$this->getCriterionName($criterion)]);
    }

    /**
     * Return current list of criteria.
     *
     * @return array
     */
    #[Pure] public function getCriteria(): array
    {
        if ($this->skipCriteria) {
            return [];
        }

        return $this->skipDefaultCriteria ? $this->criteria : array_merge($this->getDefaultCriteria(), $this->criteria);
    }

    /**
     * Return default criteria list.
     *
     * @return array
     */
    public function getDefaultCriteria(): array
    {
        return $this->defaultCriteria;
    }

    /**
     * Set default criteria list.
     *
     * @param array $criteria
     *
     * @return $this
     * @throws CriteriaException
     * @throws RepositoryException
     */
    public function setDefaultCriteria(array $criteria): static
    {
        $this->addCriteria($criteria, 'defaultCriteria');

        return $this;
    }

    /**
     * Apply criteria list to the given query.
     *
     * @param Model|Builder $queryModel
     * @param BaseRepositoryContract $repository
     * @return Builder|Model
     */
    public function applyCriteria(Model|Builder $queryModel, BaseRepositoryContract $repository): Model|Builder
    {
        foreach ($this->getCriteria() as $criterion) {
            if ($criterion instanceof BaseCriteriaContract) {
                $queryModel = $criterion->apply($queryModel, $repository);
            } elseif ($criterion instanceof Closure) {
                $queryModel = $criterion($queryModel, $repository);
            }
        }

        return $queryModel;
    }

    /**
     * Try to instantiate given criterion class name with this arguments.
     *
     * @param $class
     * @param $arguments
     *
     * @return mixed
     * @throws CriteriaException
     * @throws ReflectionException
     *
     */
    protected function instantiateCriterion($class, $arguments): mixed
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->implementsInterface(BaseCriteriaContract::class)) {
            throw CriteriaException::classNotImplementContract($class);
        }

        // If arguments is an associative array we can assume their order and parameter existence
        if (Arr::isAssoc($arguments)) {
            $parameters = array_column($reflection->getConstructor()->getParameters(), 'name');

            $arguments = array_filter(array_map(static fn($parameter) => $arguments[$parameter] ?? null, $parameters));
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
