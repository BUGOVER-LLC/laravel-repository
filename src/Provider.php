<?php

declare(strict_types=1);

namespace Service\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Service\Repository\Contracts\BaseRepositoryContract;
use Service\Repository\Generators\Commands\CriteriaCommand;
use Service\Repository\Generators\Commands\EntityCommand;
use Service\Repository\Generators\Commands\RepositoryCommand;
use Service\Repository\Listeners\RepositoryEventListener;
use Service\Repository\Repositories\BaseRepository;

/**
 * Class RepositoryModelProvider
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
    public function register()
    {
        $this->app->bind(BaseRepositoryContract::class, BaseRepository::class);

        // Register the event listener
        $this->app->bind('repository.listener', RepositoryEventListener::class);

        $this->commands(RepositoryCommand::class);
        $this->commands(EntityCommand::class);
        $this->commands(CriteriaCommand::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Merge config
        if (File::exists(\dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'config.php')) {
            $this->mergeConfigFrom(
                \dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'config.php',
                'repository'
            );
            // Publish config
            $this->publishesConfig('bugover/laravel-repositories');
        }

        // Subscribe the registered event listener
        /** @noinspection OffsetOperationsInspection */
        $this->app['events']->subscribe('repository.listener');
    }
}
