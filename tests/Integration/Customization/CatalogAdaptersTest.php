<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Ports\SizeTiers;

it('reads the live sizes and the products of a category from the catalog', function (): void {
    $large = SizeTierModel::factory()->create(['sort' => 20]);
    $medium = SizeTierModel::factory()->create(['sort' => 10]);
    SizeTierModel::factory()->create(['sort' => 5])->delete();
    $pizza = CategoryModel::factory()->create();
    $margherita = ProductModel::factory()->for($pizza)->create();

    expect(app(SizeTiers::class)->ids())->toBe([$medium->id, $large->id])
        ->and(app(CustomizableProducts::class)->idsInCategory($pizza->id))->toBe([$margherita->id])
        ->and(app(CustomizableProducts::class)->exists($margherita->id))->toBeTrue();
});
