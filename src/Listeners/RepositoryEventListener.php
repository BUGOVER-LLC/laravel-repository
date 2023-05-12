<?php

declare(strict_types=1);

namespace Service\Repository\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use JetBrains\PhpStorm\NoReturn;

/**
 * Class RepositoryEventListener
 * @package Service\Repository\Listeners
 */
class RepositoryEventListener
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param Dispatcher $dispatcher
     */
    public function subscribe(Dispatcher $dispatcher): void
    {
        $dispatcher->listen('*.entity.retrieved', self::class . '@entityRetrieved');
        $dispatcher->listen('*.entity.creating', self::class . '@entityCreating');
        $dispatcher->listen('*.entity.created', self::class . '@entityCreated');
        $dispatcher->listen('*.entity.updating', self::class . '@entityUpdating');
        $dispatcher->listen('*.entity.updated', self::class . '@entityUpdated');
        $dispatcher->listen('*.entity.deleting', self::class . '@entityDeleting');
        $dispatcher->listen('*.entity.deleted', self::class . '@entityDeleted');
        $dispatcher->listen('*.entity.forceDeleted', self::class . '@entityForceDeleted');
    }

    /**
     * @param string $event_name
     * @param array $data
     */
    #[NoReturn] public function entityRetrieved(string $event_name, array $data): void
    {
        //
    }

    /**
     * Listen to entities being created.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityCreating(string $event_name, array $data): void
    {
        if ('uuid' === config('repository.increment')) {
            // @todo
        }
    }

    /**
     * Listen to entities created.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityCreated(string $event_name, array $data): void
    {
        $clearOn = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && \in_array('create', $clearOn, true)) {
            $data[0]->forgetCache();
        }
    }

    /**
     * Listen to entities being updated.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityUpdating(string $event_name, array $data): void
    {
        //
    }

    /**
     * Listen to entities updated.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityUpdated(string $event_name, array $data): void
    {
        $clearOn = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && \in_array('update', $clearOn, true)) {
            $data[0]->forgetCache();
        }
    }

    /**
     * Listen to entities being deleted.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityDeleting(string $event_name, array $data): void
    {
        //
    }

    /**
     * Listen to entities deleted.
     *
     * @param string $event_name
     * @param array $data
     *
     * @return void
     */
    public function entityDeleted(string $event_name, array $data): void
    {
        $clearOn = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && \in_array('delete', $clearOn, true)) {
            $data[0]->forgetCache();
        }
    }

    /**
     * @param string $event_name
     * @param array $data
     */
    public function entityForceDeleted(string $event_name, array $data): void
    {
        //
    }
}
