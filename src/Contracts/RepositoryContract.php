<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Service\Repository\Repositories\Repository;

interface RepositoryContract
{
    /**
     * Dynamically pass missing static methods to the model.
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters);

    public function getConnection(): string;

    public function setConnection($name): Repository|EloquentRepositoryContract|static;

    public function getRepositoryId(): string;

    public function setRepositoryId($repositoryId): Repository|EloquentRepositoryContract|static;

    public function createModel(): Model;

    public function getModel(): string;

    public function setModel($model): static;

    public function getContainer($service = null);

    public function setContainer(Container $container): static;

    public function getFieldsSearchable(): array;

    public function getFillable(): array;

    public function model($attributes = []): Model;

    public function getKeyName(): ?string;

    public function getMap(): ?string;

    public function getTable(): ?string;

    /**
     * Dynamically pass missing methods to the model.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters);
}
