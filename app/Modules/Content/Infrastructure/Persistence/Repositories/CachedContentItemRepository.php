<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

/**
 * Caches the list of each content type and clears it after every change. Whether an item is
 * inside its dates is decided later, with the clock, so a cached list never goes stale by time.
 */
final class CachedContentItemRepository implements ContentItemRepository
{
    public function __construct(
        private readonly ContentItemRepository $inner,
        private readonly Cache $cache,
    ) {}

    public static function cacheKey(ContentType $type): string
    {
        return "content.items.{$type->value}";
    }

    /**
     * The cache holds plain arrays, not objects: an old cache entry can then never break a new class.
     */
    public function all(ContentType $type): array
    {
        /** @var list<array{id: int, values: array<string, string>, starts: ?string, ends: ?string, active: bool, sort: int}> $rows */
        $rows = $this->cache->rememberForever(self::cacheKey($type), fn (): array => array_map(
            fn (ContentItem $item): array => [
                'id' => (int) $item->id,
                'values' => $item->values,
                'starts' => $item->schedule->start()?->format(DATE_ATOM),
                'ends' => $item->schedule->end()?->format(DATE_ATOM),
                'active' => $item->isActive,
                'sort' => $item->sort->value(),
            ],
            $this->inner->all($type),
        ));

        return array_map(fn (array $row): ContentItem => ContentItem::reconstitute(
            $row['id'],
            $type,
            $row['values'],
            DateRange::between(
                $row['starts'] === null ? null : new DateTimeImmutable($row['starts']),
                $row['ends'] === null ? null : new DateTimeImmutable($row['ends']),
            ),
            $row['active'],
            SortOrder::fromInt($row['sort']),
        ), $rows);
    }

    public function find(ContentType $type, int $id): ?ContentItem
    {
        return $this->inner->find($type, $id);
    }

    public function save(ContentItem $item): int
    {
        $id = $this->inner->save($item);
        $this->clear($item->type);

        return $id;
    }

    public function delete(ContentType $type, int $id): void
    {
        $this->inner->delete($type, $id);
        $this->clear($type);
    }

    public function restore(ContentType $type, int $id): void
    {
        $this->inner->restore($type, $id);
        $this->clear($type);
    }

    public function nextSortOrder(ContentType $type): SortOrder
    {
        return $this->inner->nextSortOrder($type);
    }

    private function clear(ContentType $type): void
    {
        DB::afterCommit(fn () => $this->cache->forget(self::cacheKey($type)));
    }
}
