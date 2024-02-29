<?php

declare(strict_types=1);

namespace Service\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Service\Repository\Contracts\BaseRepositoryContract;
use Service\Repository\Listeners\RepositoryEventListener;
use Service\Repository\Repositories\EloquentRepository;

/**
 * Class RepositoryModelProvider
 *
 * @package Service\Repository\Providers
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * The repository alias pattern.
     *
     * @var string
     */
    protected string $repositoryAliasPattern = '{{class}}Contract';

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/repository.php', 'repository');

        $this->app->bind(BaseRepositoryContract::class, EloquentRepository::class);

        // Register the event listener
        $this->app->bind('repository.listener', RepositoryEventListener::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/repository.php' => base_path('config/repository.php')], 'config');

        /**
         * @noinspection OffsetOperationsInspection
         */
        $this->app['events']->subscribe('repository.listener');
    }
}