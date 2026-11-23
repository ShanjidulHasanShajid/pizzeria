<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedHomeLayoutRepository;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(CachedHomeLayoutRepository::CACHE_KEY);
    $this->layouts = app(HomeLayoutRepository::class);
});

it('is empty at first, and saves and replaces the layout', function (): void {
    [$pizza, $pasta] = CategoryModel::factory()->count(2)->create()->all();

    expect($this->layouts->get()->tabCategoryIds)->toBe([]);

    $this->layouts->save(new HomeLayout([$pasta->id, $pizza->id], [new HomeRow($pizza->id, [5, 3])]));
    expect($this->layouts->get()->tabCategoryIds)->toBe([$pasta->id, $pizza->id])
        ->and($this->layouts->get()->rows[0]->productIds)->toBe([5, 3]);

    $this->layouts->save(new HomeLayout([$pizza->id], []));
    expect($this->layouts->get()->tabCategoryIds)->toBe([$pizza->id])
        ->and($this->layouts->get()->rows)->toBe([]);
    $this->assertDatabaseCount('home_sections', 1);
});

it('knows the categories and the visible products of the catalog', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Drinks']);
    $coffee = CategoryModel::factory()->childOf($drinks)->create(['name' => 'Coffee']);
    $latte = ProductModel::factory()->for($coffee)->create(['name' => 'Latte']);
    ProductModel::factory()->for($coffee)->unpublished()->create();

    $catalog = app(HomeCatalog::class);

    expect($catalog->categoryNames())->toBe([$drinks->id => 'Drinks', $coffee->id => 'Drinks > Coffee'])
        ->and($catalog->productNames($drinks->id))->toBe([$latte->id => 'Latte']);
});
