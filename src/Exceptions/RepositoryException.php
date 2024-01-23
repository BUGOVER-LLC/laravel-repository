<?php

declare(strict_types=1);

namespace Service\Repository\Exceptions;

use Exception;
use JetBrains\PhpStorm\Pure;

use function get_class;

class RepositoryException extends Exception
{
    /**
     * @param $list
     * @param $object
     * @return static
     */
    #[Pure] public static function listNotFound($list, $object): static
    {
        return new static('Given list "' . $list . '" not found in ' . get_class($object) . ' class');
    }
}
