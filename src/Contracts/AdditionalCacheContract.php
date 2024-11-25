<?php

declare(strict_types=1);

namespace Service\Repository\Contracts;

/**
 * Interface when if used advanced cache on one session only
 *
 * @package Service\Repository\Contracts
 */
interface AdditionalCacheContract
{
    /**
     * Get token value in the request header by name
     *
     * @return string|null
     */
    public function getTempoToken(): ?string;

    /**
     * Get is enable session cache
     *
     * @return bool
     */
    public function isCacheOnSessionEnabled(): bool;
}
