<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Queries\ProductSummaryQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->category = CategoryModel::factory()->create();
    $this->product = fn (array $attributes = []): ProductModel => ProductModel::factory()->for($this->category)->create($attributes);
    $this->summaries = fn (array $ids): array => app(ProductSummaryQuery::class)->byIds($ids);
});

it('gives name, slug and the thumbnail of the main image', function (): void {
    $product = ($this->product)(['name' => 'Margherita', 'slug' => 'margherita']);
    ProductImageModel::factory()->for($product)->main()->create(['path' => 'products/big.webp', 'thumbnail_path' => 'products/small.webp']);

    $summary = ($this->summaries)([$product->id])[$product->id];

    expect($summary->name)->toBe('Margherita')
        ->and($summary->slug)->toBe('margherita')
        ->and($summary->imagePath)->toBe('products/small.webp')
        ->and($summary->isStandard)->toBeTrue()
        ->and($summary->isVisible)->toBeTrue();
});

it('also knows hidden and deleted products, marked as not visible', function (): void {
    $hidden = ($this->product)(['is_published' => false]);
    $deleted = ($this->product)(['name' => 'Old pizza']);
    $deleted->delete();

    $summaries = ($this->summaries)([$hidden->id, $deleted->id, 424242]);

    expect($summaries)->toHaveCount(2)
        ->and($summaries[$hidden->id]->isVisible)->toBeFalse()
        ->and($summaries[$deleted->id]->name)->toBe('Old pizza')
        ->and($summaries[$deleted->id]->isVisible)->toBeFalse();
});

it('marks a combo as not standard', function (): void {
    $combo = ProductModel::factory()->combo()->for($this->category)->create();

    expect(($this->summaries)([$combo->id])[$combo->id]->isStandard)->toBeFalse();
});

it('answers no ids without asking the database', function (): void {
    DB::enableQueryLog();

    expect(($this->summaries)([]))->toBe([])
        ->and(DB::getQueryLog())->toBe([]);
});

it('gives the pricing facts of several products with as many queries as for one', function (): void {
    $ids = array_map(fn (): int => ($this->product)()->id, range(1, 5));
    $hidden = ($this->product)(['is_published' => false]);
    $query = app(StorefrontCatalogQuery::class);
    $count = function (array $ids) use ($query): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $query->pricingFactsFor($ids);

        return count(DB::getQueryLog());
    };

    $facts = $query->pricingFactsFor([...$ids, $hidden->id, 424242]);

    expect($facts)->toHaveCount(6)
        ->and($facts[$ids[0]]->isVisible)->toBeTrue()
        ->and($facts[$hidden->id]->isVisible)->toBeFalse()
        ->and($count($ids))->toBe($count([$ids[0]]));
});
