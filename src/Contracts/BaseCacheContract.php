<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

/**
 * Interface BaseCacheContract
 * @package Service\Repository\Contracts
 */
interface BaseCacheContract
{
    /**
     * Set the repository cache lifetime.
     *
     * @param  int  $cacheLifetime
     *
     * @return $this
     */
    public function setCacheLifetime($cacheLifetime): self;

    /**
     * Get the repository cache lifetime.
     *
     * @return int
     */
    public function getCacheLifetime(): ?int;

    /**
     * Set the repository cache driver.
     *
     * @param  string  $cacheDriver
     *
     * @return $this
     */
    public function setCacheDriver($cacheDriver): self;

    /**
     * Get the repository cache driver.
     *
     * @return string|null
     */
    public function getCacheDriver(): ?string;

    /**
     * Enable repository cache clear.
     *
     * @param  bool  $status
     *
     * @return $this
     */
    public function enableCacheClear(bool $status = true): self;

    /**
     * Determine if repository cache clear is enabled.
     *
     * @return bool
     */
    public function isCacheClearEnabled(): bool;

    /**
     * Forget the repository cache.
     *
     * @return BaseCacheContract
     */
    public function forgetCache(): self;
}
