<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Application\Ports\SizeTierPrices;

/**
 * Says which sizes option prices use, instead of the Customization module.
 */
final class InMemorySizeTierPrices implements SizeTierPrices
{
    /** @var array<int, true> */
    private array $used = [];

    /** @var array<int, true> */
    private array $leftovers = [];

    public function usesSizeTier(int $sizeTierId): bool
    {
        return isset($this->used[$sizeTierId]);
    }

    public function usesSizeTierIncludingTrash(int $sizeTierId): bool
    {
        return isset($this->used[$sizeTierId]) || isset($this->leftovers[$sizeTierId]);
    }

    // Test helpers (not part of the interface)

    public function markUsed(int $sizeTierId): void
    {
        $this->used[$sizeTierId] = true;
    }

    public function markLeftover(int $sizeTierId): void
    {
        $this->leftovers[$sizeTierId] = true;
    }
}
