<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\SettingsRepository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

/**
 * A decorator: the same interface around the real repository, plus a cache.
 * Settings are read on every page and change rarely.
 */
final class CachedSettingsRepository implements SettingsRepository
{
    public const CACHE_KEY = 'content.settings';

    public function __construct(
        private readonly SettingsRepository $inner,
        private readonly Cache $cache,
    ) {}

    public function all(): array
    {
        /** @var array<string, string> */
        return $this->cache->rememberForever(self::CACHE_KEY, fn (): array => $this->inner->all());
    }

    /**
     * The cache is cleared after the commit. Clearing it earlier would let another request
     * fill it again with the old values, and they would stay there.
     */
    public function save(array $values): void
    {
        $this->inner->save($values);

        DB::afterCommit(fn () => $this->cache->forget(self::CACHE_KEY));
    }
}
