<?php

declare(strict_types=1);

namespace Service\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Service\Repository\Contracts\BaseRepositoryContract;
use Service\Repository\Listeners\RepositoryEventListener;
use Service\Repository\Repositories\BaseRepository;

use function dirname;

/**
 * Class RepositoryModelProvider
 *
 * @package Service\Repository\Providers
 */
class Provider extends ServiceProvider
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
        $this->app->bind(BaseRepositoryContract::class, BaseRepository::class);

        // Register the event listener
        $this->app->bind('repository.listener', RepositoryEventListener::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Merge config
        if (File::exists(dirname(__DIR__) . DS . 'config' . DS . 'package' . DS . 'config.php')) {
            $this->mergeConfigFrom(
                dirname(__DIR__) . DS . 'config' . DS . 'package' . DS . 'config.php',
                'repository'
            );
            // Publish config
            $this->publishesConfig('bugover/laravel-repositories');
        }

        // Subscribe the registered event listener
        /**
         * @noinspection OffsetOperationsInspection
         */
        $this->app['events']->subscribe('repository.listener');
    }
}
