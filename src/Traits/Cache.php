<?php

declare(strict_types=1);

namespace Service\Repository\Traits;

use Closure;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Service\Repository\Repositories\Repository;

use function in_array;
use function is_array;

/**
 * Trait Cache
 *
 * @package Service\Repository\Traits
 * @method resetRepository()
 */
trait Cache
{
    /**
     * The repository cache lifetime.
     *
     * @var null|int
     */
    protected ?int $cacheLifetime = null;

    /**
     * The repository cache driver.
     *
     * @var null|string
     */
    protected ?string $cacheDriver = null;

    /**
     * Indicate if the repository cache clear is enabled.
     *
     * @var bool
     */
    protected bool $cacheClearEnabled = true;

    /**
     * {@inheritdoc}
     */
    public function enableCacheClear(bool $status = true): self
    {
        $this->cacheClearEnabled = $status;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isCacheClearEnabled(): bool
    {
        return $this->cacheClearEnabled;
    }

    /**
     * {@inheritdoc}
     *
     * @return Cache|Repository
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function forgetCache(): self
    {
        if ($this->getCacheLifetime()) {
            if (method_exists($this->getContainer('cache')->getStore(), 'tags')) {
                $this->getContainer('cache')->tags($this->getRepositoryId())->flush();
            } else {
                foreach ($this->flushCacheKeys() as $cacheKey) {
                    $this->getContainer('cache')->forget($cacheKey);
                }
            }

            $this->getContainer('events')->dispatch($this->getRepositoryId() . '.entity.cache.flushed', [$this]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime ?? $this->getContainer('config')->get('repository.cache.lifetime');
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheLifetime($cacheLifetime): self
    {
        $this->cacheLifetime = $cacheLifetime;

        return $this;
    }

    /**
     * Flush cache keys by mimicking cache tags.
     *
     * @return array
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function flushCacheKeys(): array
    {
        $flushedKeys = [];
        $calledClass = static::class;
        $config = $this->getContainer('config')->get('repository.cache');
        $cacheKeys = $this->getCacheKeys($config['keys_file']);

        if (isset($cacheKeys[$calledClass]) && is_array($cacheKeys[$calledClass])) {
            foreach ($cacheKeys[$calledClass] as $cacheKey) {
                $flushedKeys[] = $calledClass . '@' . $cacheKey;
            }

            unset($cacheKeys[$calledClass]);
            file_put_contents($config['keys_file'], json_encode($cacheKeys, JSON_THROW_ON_ERROR));
        }

        return $flushedKeys;
    }

    /**
     * Get cache keys.
     *
     * @param $file
     * @return array
     * @throws JsonException
     */
    protected function getCacheKeys($file): array
    {
        if (!file_exists($file)) {
            file_put_contents($file, null);
        }

        return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) ?: [];
    }

    /**
     * Cache given callback.
     *
     * @param string $class
     * @param string $method
     * @param array $args
     * @param Closure $closure
     *
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    protected function cacheCallback(string $class, string $method, array $args, Closure $closure): mixed
    {
        $repository_id = $this->getRepositoryId();
        $lifetime = $this->getCacheLifetime();
        $hash = $this->generateCacheHash($args);
        $cache_key = $class . '@' . $method . '.' . $hash;

        // Switch cache driver on runtime
        if ($driver = $this->getCacheDriver()) {
            $this->getContainer('cache')->setDefaultDriver($driver);
        }

        // We need cache tags, check if default driver supports it
        if (method_exists($this->getContainer('cache')->getStore(), 'tags')) {
            $result = -1 === $lifetime
                ? $this->getContainer('cache')->tags($repository_id)->rememberForever($cache_key, $closure)
                : $this->getContainer('cache')->tags($repository_id)->remember($cache_key, $lifetime, $closure);

            // We're done, let's clean up!
            $this->resetRepository();

            return $result;
        }

        // Default cache driver doesn't support tags, let's do it manually
        $this->storeCacheKeys($class, $method, $hash);

        $result = -1 === $lifetime
            ? $this->getContainer('cache')->rememberForever($cache_key, $closure)
            : $this->getContainer('cache')->remember($cache_key, $lifetime, $closure);

        // We're done, let's clean up!
        $this->resetCachedRepository();

        return $result;
    }

    /**
     * Generate unique query hash.
     *
     * @param $args
     *
     * @return string
     * @throws JsonException
     */
    protected function generateCacheHash($args): string
    {
        return md5(
            json_encode(
                $args + [
                    $this->getRepositoryId(),
                    $this->getModel(),
                    $this->getCacheDriver(),
                    $this->getCacheLifetime(),
                    $this->whereJson,
                    $this->whereExists,
                    $this->whereJsonNotIn,
                    $this->orWhereJson,
                    $this->whereJsonCount,
                    $this->when,
                    $this->has,
                    $this->orHas,
                    $this->orWhereHas,
                    $this->doesntHave,
                    $this->orDoesntHave,
                    $this->whereDoesntHave,
                    $this->orWhereDoesntHave,
                    $this->hasMorph,
                    $this->whereHasMorph,
                    $this->whereBetween,
                    $this->orWhereBetween,
                    $this->whereNotBetween,
                    $this->whereDate,
                    $this->whereMonth,
                    $this->whereDay,
                    $this->whereTime,
                    $this->withCount,
                    $this->withExists,
                    $this->withMax,
                    $this->withMin,
                    $this->withAvg,
                    $this->withSum,
                    $this->whereRaw,
                    $this->orWhereRaw,
                    $this->except,
                    $this->relations,
                    $this->where,
                    $this->orWhere,
                    $this->whereIn,
                    $this->whereNotIn,
                    $this->whereHas,
                    $this->scopes,
                    $this->orderBy,
                    $this->groupBy,
                    $this->having,
                    $this->havingRaw,
                    $this->join,
                    $this->offset,
                    $this->limit,
                    $this->withTrashed,
                    $this->withoutScope,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDriver(): ?string
    {
        return $this->cacheDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheDriver($cacheDriver): self
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Store cache keys by mimicking cache tags.
     *
     * @param string $class
     * @param string $method
     * @param string $hash
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    protected function storeCacheKeys(string $class, string $method, string $hash): void
    {
        $keysFile = $this->getContainer('config')->get('repository.cache.keys_file');
        $cacheKeys = $this->getCacheKeys($keysFile);

        if (!isset($cacheKeys[$class]) || !in_array($method . '.' . $hash, $cacheKeys[$class], true)) {
            $cacheKeys[$class][] = $method . '.' . $hash;
            file_put_contents($keysFile, json_encode($cacheKeys, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Reset cached repository to its defaults.
     *
     * @return Repository|Cache
     */
    protected function resetCachedRepository(): self
    {
        $this->resetRepository();

        $this->cacheLifetime = null;
        $this->cacheDriver = null;

        return $this;
    }
}
