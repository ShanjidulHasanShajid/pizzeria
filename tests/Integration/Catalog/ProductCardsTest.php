<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Presentation\Support\ProductCards;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * An available standard product with one size for ৳590.
 *
 * @param  array<string, mixed>  $changes
 */
function shopCard(array $changes = []): ProductCard
{
    return new ProductCard(...[
        'id' => 1, 'slug' => 'margherita', 'name' => 'Margherita', 'shortDescription' => null, 'type' => ProductType::Standard,
        'fromPrice' => Money::fromTaka(590), 'hasSeveralPrices' => false, 'variantLabels' => ['Regular'], 'imagePath' => null,
        'tags' => [], 'isNew' => false, 'isHot' => false, 'leadTimeLabel' => '20 minutes', 'leadTimeClass' => LeadTimeClass::MadeToOrder,
        'isAvailable' => true, 'hasOptions' => false, 'onlyVariantId' => 7, ...$changes,
    ]);
}

it('chooses the button by the kind of product', function (array $changes, string $state): void {
    expect(app(ProductCards::class)->toArray(shopCard($changes))['state'])->toBe($state);
})->with([
    'one size' => [[], 'add'],
    'one size, but options' => [['hasOptions' => true], 'options'],
    'several sizes' => [['variantLabels' => ['Small', 'Large'], 'onlyVariantId' => null], 'options'],
    'pizza builder' => [['type' => ProductType::Builder], 'options'],
    'combo' => [['type' => ProductType::Combo], 'combo'],
    'sold out combo' => [['type' => ProductType::Combo, 'isAvailable' => false], 'soldout'],
]);

it('shows the price, the image and the badges', function (): void {
    $plain = app(ProductCards::class)->toArray(shopCard());
    $rich = app(ProductCards::class)->toArray(shopCard([
        'imagePath' => 'products/a.webp', 'isNew' => true, 'isHot' => true, 'leadTimeClass' => LeadTimeClass::NextDay, 'leadTimeLabel' => 'Next day',
        'tags' => [new TagBadge('Vegetarian', 'vegetarian', TagType::Dietary, null), new TagBadge('Contains nuts', 'nuts', TagType::Allergen, null)],
    ]));

    expect($plain['price'])->toBe(59_000)
        ->and($plain['id'])->toBe(1)
        ->and($plain['variantId'])->toBe(7)
        ->and($plain['description'])->toBe('')
        ->and($plain['badges'])->toBe([])
        ->and($plain['sizes'])->toBe([]) // one size needs no chip
        ->and($plain['tags'])->toBe([])
        ->and($plain['image'])->toContain('placeholder.svg')
        ->and($rich['image'])->toEndWith('products/a.webp')
        ->and(array_column($rich['badges'], 'label'))->toBe(['New', 'Hot', 'Next day'])
        ->and(array_column($rich['badges'], 'variant'))->toBe(['new', 'hot', 'lead'])
        ->and(array_column($rich['tags'], 'label'))->toBe(['Vegetarian', 'Contains nuts'])
        ->and(app(ProductCards::class)->toArray(shopCard(['variantLabels' => ['Small', 'Large']]))['sizes'])->toBe(['Small', 'Large']);
});
