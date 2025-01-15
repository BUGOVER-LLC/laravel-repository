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

    /**
     * @return string
     */
    public function getConnection(): string;

    /**
     * @param $name
     * @return Repository|EloquentRepositoryContract|$this
     */
    public function setConnection(string $name): Repository|EloquentRepositoryContract|static;

    /**
     * @return string
     */
    public function getRepositoryId(): string;

    /**
     * @param string $repositoryId
     * @return Repository|EloquentRepositoryContract|$this
     */
    public function setRepositoryId(string $repositoryId): Repository|EloquentRepositoryContract|static;

    /**
     * @return Model
     */
    public function createModel(): Model;

    /**
     * @return string
     */
    public function getModel(): string;

    /**
     * @param string $model
     * @return $this
     */
    public function setModel(string $model): static;

    /**
     * @param $service
     * @return mixed
     */
    public function getContainer($service = null);

    /**
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container): static;

    /**
     * @return array
     */
    public function getFieldsSearchable(): array;

    /**
     * @return array
     */
    public function getFillable(): array;

    /**
     * @param $attributes
     * @return Model
     */
    public function model($attributes = []): Model;

    /**
     * @return string|null
     */
    public function getKeyName(): ?string;

    /**
     * @return string|null
     */
    public function getMap(): ?string;

    /**
     * @return string|null
     */
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
