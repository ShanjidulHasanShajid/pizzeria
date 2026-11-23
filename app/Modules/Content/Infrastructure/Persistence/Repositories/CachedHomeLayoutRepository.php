<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

final class CachedHomeLayoutRepository implements HomeLayoutRepository
{
    public const CACHE_KEY = 'content.home-layout';

    public function __construct(
        private readonly HomeLayoutRepository $inner,
        private readonly Cache $cache,
    ) {}

    public function get(): HomeLayout
    {
        /** @var array{tabs: list<int>, rows: list<array{category: int, products: list<int>}>} $data */
        $data = $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            $layout = $this->inner->get();

            return [
                'tabs' => $layout->tabCategoryIds,
                'rows' => array_map(fn (HomeRow $row): array => ['category' => $row->categoryId, 'products' => $row->productIds], $layout->rows),
            ];
        });

        return new HomeLayout(
            $data['tabs'],
            array_map(fn (array $row): HomeRow => new HomeRow($row['category'], $row['products']), $data['rows']),
        );
    }

    public function save(HomeLayout $layout): void
    {
        $this->inner->save($layout);

        DB::afterCommit(fn () => $this->cache->forget(self::CACHE_KEY));
    }
}
