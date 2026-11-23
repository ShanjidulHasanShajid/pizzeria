<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use App\Modules\Content\Infrastructure\Persistence\Models\HomeSection;
use Illuminate\Support\Facades\DB;

final class EloquentHomeLayoutRepository implements HomeLayoutRepository
{
    private const TAB = 'tab';

    private const ROW = 'row';

    public function get(): HomeLayout
    {
        $tabs = [];
        $rows = [];

        foreach (HomeSection::query()->orderBy('sort')->orderBy('id')->get() as $section) {
            if ($section->kind === self::TAB) {
                $tabs[] = $section->category_id;
            } else {
                $rows[] = new HomeRow($section->category_id, array_values(array_map('intval', $section->product_ids ?? [])));
            }
        }

        return new HomeLayout($tabs, $rows);
    }

    /**
     * The layout is small and has no history, so it is simply written again.
     */
    public function save(HomeLayout $layout): void
    {
        DB::transaction(function () use ($layout): void {
            HomeSection::query()->delete();

            foreach ($layout->tabCategoryIds as $position => $categoryId) {
                HomeSection::query()->create(['kind' => self::TAB, 'category_id' => $categoryId, 'product_ids' => null, 'sort' => ($position + 1) * 10]);
            }

            foreach ($layout->rows as $position => $row) {
                HomeSection::query()->create(['kind' => self::ROW, 'category_id' => $row->categoryId, 'product_ids' => $row->productIds, 'sort' => ($position + 1) * 10]);
            }
        });
    }
}
