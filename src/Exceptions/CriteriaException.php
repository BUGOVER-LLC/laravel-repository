<?php

declare(strict_types=1);

namespace Service\Repository\Exceptions;

use Exception;
use JetBrains\PhpStorm\Pure;
use Service\Repository\Contracts\BaseCriteriaContract;

/**
 * Class CriteriaException
 * @package Service\Repository\Exceptions
 */
class CriteriaException extends Exception
{
    /**
     * @param $criterion
     * @return static
     */
    public static function wrongCriterionType($criterion): static
    {
        $type = gettype($criterion);
        $value = 'object' === $type ? \get_class($criterion) : $criterion;

        return new static('Given criterion with type ' . $type . ' and value ' . $value . ' is not allowed');
    }

    /**
     * @param $criterionClassName
     * @return static
     */
    #[Pure] public static function classNotImplementContract($criterionClassName): static
    {
        return new static('Given ' . $criterionClassName . ' class is not implement ' . BaseCriteriaContract::class . 'contract');
    }

    /**
     * @param array $criterion
     * @return static
     */
    public static function wrongArraySignature(array $criterion): static
    {
        return new static(
            'Array signature for criterion instantiating must contain only two elements in case of sequential array and one in case of assoc array. ' .
            'Array with length "' . count($criterion) . '" given'
        );
    }
}
