<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Service\Repository\Exceptions\RepositoryException;

trait Magick
{

    /**
     * {@inheritdoc}
     */
    public static function __callStatic($method, $parameters)
    {
        return \call_user_func_array([new static(), $method], $parameters);
    }

    /**
     * {@inheritdoc}
     * @throws RepositoryException|BindingResolutionException
     */
    public function __call(string $method, array $parameters)
    {
        if (method_exists($this->createModel(), 'scope' . ucfirst($method))) {
            $this->scope($method, $parameters);

            return $this;
        }

        return \call_user_func_array([$this->createModel(), $method], $parameters);
    }
}
