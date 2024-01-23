<?php

declare(strict_types=1);

namespace Service\Repository\Listeners;

use Illuminate\Contracts\Events\Dispatcher;

use function in_array;

/**
 * Class RepositoryEventListener
 *
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
        $dispatcher->listen('*.entity.created', self::class . '@entityCreated');
        $dispatcher->listen('*.entity.updated', self::class . '@entityUpdated');
        $dispatcher->listen('*.entity.deleted', self::class . '@entityDeleted');
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
        $clear_on = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && in_array('create', $clear_on, true)) {
            $data[0]->forgetCache();
        }
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
        $clear_on = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && in_array('update', $clear_on, true)) {
            $data[0]->forgetCache();
        }
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
        $clear_on = $data[0]->getContainer('config')->get('repository.cache.clear_on');

        if ($data[0]->isCacheClearEnabled() && in_array('delete', $clear_on, true)) {
            $data[0]->forgetCache();
        }
    }
}
