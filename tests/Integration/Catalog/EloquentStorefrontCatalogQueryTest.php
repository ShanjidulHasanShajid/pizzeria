<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CategoryNode;
use App\Modules\Catalog\Application\DTOs\ProductCard;
use App\Modules\Catalog\Application\DTOs\ProductImageView;
use App\Modules\Catalog\Application\DTOs\TagBadge;
use App\Modules\Catalog\Application\DTOs\VariantPricingFacts;
use App\Modules\Catalog\Application\DTOs\VariantView;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use Illuminate\Support\Facades\DB;

/**
 * A published product in $category with one variant per entry: label => [price in poisha, available].
 * The first variant is the default. $attributes changes product columns (name, sort, is_hot ...).
 *
 * @param  array<string, array{0: int, 1: bool}>  $variants
 * @param  array<string, mixed>  $attributes
 */
function shopProduct(CategoryModel $category, array $variants = ['Regular' => [30_000, true]], array $attributes = []): ProductModel
{
    $factory = ProductModel::factory()->for($category)->state($attributes);
    $sort = 10;

    foreach ($variants as $label => [$price, $isAvailable]) {
        $factory = $factory->has(ProductVariantModel::factory()->state([
            'label' => $label,
            'price' => $price,
            'is_available' => $isAvailable,
            'is_default' => $sort === 10,
            'sort' => $sort,
        ]), 'variants');
        $sort += 10;
    }

    return $factory->create();
}

/**
 * @param  list<ProductCard>  $cards
 * @return list<int>
 */
function cardIds(array $cards): array
{
    return array_map(fn (ProductCard $card): int => $card->id, $cards);
}

beforeEach(function (): void {
    $this->query = app(StorefrontCatalogQuery::class);
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza', 'slug' => 'pizza', 'sort' => 10]);
});

describe('prices and availability on the cards', function (): void {
    it('shows the cheapest available variant as the "from" price', function (): void {
        shopProduct($this->pizza, ['Small' => [30_000, false], 'Personal' => [45_000, true], 'Large' => [125_000, true]]);

        $card = $this->query->productCardsForCategory($this->pizza->id)[0];

        expect($card->fromPrice->poisha())->toBe(45_000)
            ->and($card->hasSeveralPrices)->toBeTrue()
            ->and($card->variantLabels)->toBe(['Small', 'Personal', 'Large'])
            ->and($card->isAvailable)->toBeTrue();
    });

    it('shows one exact price when all available variants cost the same', function (): void {
        shopProduct($this->pizza, ['Can' => [8_000, true], 'Bottle' => [8_000, true], 'Mini' => [5_000, false]]);

        $card = $this->query->productCardsForCategory($this->pizza->id)[0];

        expect($card->fromPrice->poisha())->toBe(8_000)
            ->and($card->hasSeveralPrices)->toBeFalse();
    });

    it('falls back to the cheapest variant and shows sold out when no variant is available', function (): void {
        shopProduct($this->pizza, ['Personal' => [45_000, false], 'Large' => [125_000, false]]);

        $card = $this->query->productCardsForCategory($this->pizza->id)[0];

        expect($card->fromPrice->poisha())->toBe(45_000)
            ->and($card->isAvailable)->toBeFalse();
    });

    it('still lists a product that is sold out today, marked as sold out', function (): void {
        shopProduct($this->pizza, attributes: ['is_available' => false]);

        $cards = $this->query->productCardsForCategory($this->pizza->id);

        expect($cards)->toHaveCount(1)
            ->and($cards[0]->isAvailable)->toBeFalse();
    });

    it('ignores deleted variants', function (): void {
        $product = shopProduct($this->pizza, ['Personal' => [45_000, true], 'Mini' => [20_000, true]]);
        $product->variants()->where('label', 'Mini')->firstOrFail()->delete();

        $card = $this->query->productCardsForCategory($this->pizza->id)[0];

        expect($card->fromPrice->poisha())->toBe(45_000)
            ->and($card->variantLabels)->toBe(['Personal']);
    });
});

describe('what the shop shows', function (): void {
    it('hides unpublished and deleted products and products without variants', function (): void {
        $visible = shopProduct($this->pizza);
        shopProduct($this->pizza, attributes: ['is_published' => false]);
        shopProduct($this->pizza)->delete();
        shopProduct($this->pizza)->variants()->delete();

        expect(cardIds($this->query->productCardsForCategory($this->pizza->id)))->toBe([$visible->id]);
    });

    it('hides products of inactive or deleted categories and of sub-categories with an inactive parent', function (): void {
        $visible = shopProduct($this->pizza, attributes: ['name' => 'Mango Pizza']);

        $closed = CategoryModel::factory()->inactive()->create();
        shopProduct($closed, attributes: ['name' => 'Mango Pie']);

        $drinks = CategoryModel::factory()->inactive()->create();
        $shakes = CategoryModel::factory()->childOf($drinks)->create();
        shopProduct($shakes, attributes: ['name' => 'Mango Lassi']);

        $old = CategoryModel::factory()->create();
        shopProduct($old, attributes: ['name' => 'Mango Cake']);
        $old->delete();

        expect(cardIds($this->query->search('Mango', 10)))->toBe([$visible->id])
            ->and($this->query->productCardsForCategory($shakes->id))->toBe([]);
    });

    it('lists the products of active sub-categories on a top-level category page, in sort order', function (): void {
        $drinks = CategoryModel::factory()->create();
        $shakes = CategoryModel::factory()->childOf($drinks)->create();
        $closed = CategoryModel::factory()->childOf($drinks)->inactive()->create();
        $water = shopProduct($drinks, attributes: ['sort' => 20]);
        $shake = shopProduct($shakes, attributes: ['sort' => 10]);
        shopProduct($closed);

        expect(cardIds($this->query->productCardsForCategory($drinks->id)))->toBe([$shake->id, $water->id])
            ->and(cardIds($this->query->productCardsForCategory($shakes->id)))->toBe([$shake->id]);
    });

    it('lists hot products only, up to the limit', function (): void {
        $first = shopProduct($this->pizza, attributes: ['is_hot' => true, 'sort' => 10]);
        $second = shopProduct($this->pizza, attributes: ['is_hot' => true, 'sort' => 20]);
        shopProduct($this->pizza, attributes: ['is_hot' => true, 'sort' => 30]);
        shopProduct($this->pizza, attributes: ['sort' => 5]);

        expect(cardIds($this->query->hotProducts(2)))->toBe([$first->id, $second->id]);
    });

    it('shows other products of the same category as related products', function (): void {
        $margherita = shopProduct($this->pizza, attributes: ['sort' => 10]);
        $pepperoni = shopProduct($this->pizza, attributes: ['sort' => 20]);
        $hawaiian = shopProduct($this->pizza, attributes: ['sort' => 30]);
        shopProduct(CategoryModel::factory()->create());

        expect(cardIds($this->query->relatedProducts($margherita->id, 1)))->toBe([$pepperoni->id])
            ->and(cardIds($this->query->relatedProducts($margherita->id, 10)))->toBe([$pepperoni->id, $hawaiian->id])
            ->and($this->query->relatedProducts(424242, 4))->toBe([]);
    });

    it('searches names and short descriptions and treats % as a normal character', function (): void {
        $garlicBread = shopProduct($this->pizza, attributes: ['name' => 'Garlic Bread', 'short_description' => 'Crispy.', 'sort' => 10]);
        $wings = shopProduct($this->pizza, attributes: ['name' => 'Wings', 'short_description' => 'With a garlic dip.', 'sort' => 20]);
        $cola = shopProduct($this->pizza, attributes: ['name' => 'Cola', 'short_description' => '100% sugar.', 'sort' => 30]);
        shopProduct($this->pizza, attributes: ['name' => 'Fries', 'short_description' => 'Salted.', 'sort' => 40]);

        expect(cardIds($this->query->search('GARLIC', 10)))->toBe([$garlicBread->id, $wings->id])
            ->and(cardIds($this->query->search('%', 10)))->toBe([$cola->id])
            ->and($this->query->search('   ', 10))->toBe([]);
    });
});

describe('the product page', function (): void {
    it('shows variants, images and active tags in their order', function (): void {
        $large = SizeTierModel::factory()->create(['name' => 'Large']);
        $product = shopProduct($this->pizza, ['Personal' => [45_000, true], 'Large' => [125_000, false]], ['slug' => 'margherita']);
        $product->variants()->where('label', 'Large')->update(['size_tier_id' => $large->id]);
        ProductImageModel::factory()->for($product)->create(['path' => 'products/b.webp', 'sort' => 20]);
        ProductImageModel::factory()->for($product)->main()->create(['path' => 'products/a.webp', 'sort' => 10]);
        $spicy = TagModel::factory()->create(['name' => 'Spicy', 'sort' => 20]);
        $veg = TagModel::factory()->create(['name' => 'Veg', 'sort' => 10]);
        $inactive = TagModel::factory()->inactive()->create();
        $deleted = TagModel::factory()->create();
        $product->tags()->attach([$spicy->id, $veg->id, $inactive->id, $deleted->id]);
        $deleted->delete();

        $detail = $this->query->productDetail('margherita');

        expect($detail->categoryName)->toBe('Pizza')
            ->and($detail->isAvailable)->toBeTrue()
            ->and(array_map(fn (VariantView $variant): string => $variant->label, $detail->variants))->toBe(['Personal', 'Large'])
            ->and($detail->variants[1]->sizeTierName)->toBe('Large')
            ->and($detail->variants[1]->isAvailable)->toBeFalse()
            ->and(array_map(fn (ProductImageView $image): string => $image->originalPath, $detail->images))->toBe(['products/a.webp', 'products/b.webp'])
            ->and($detail->images[0]->isMain)->toBeTrue()
            ->and($detail->images[0]->thumbnailPath)->toBe('products/a.webp')
            ->and(array_map(fn (TagBadge $tag): string => $tag->name, $detail->tags))->toBe(['Veg', 'Spicy']);
    });

    it('has no page for an unknown or unpublished product', function (): void {
        shopProduct($this->pizza, attributes: ['slug' => 'secret-pizza', 'is_published' => false]);

        expect($this->query->productDetail('secret-pizza'))->toBeNull()
            ->and($this->query->productDetail('no-such-pizza'))->toBeNull();
    });
});

describe('the mega menu', function (): void {
    it('shows active top-level categories with their active sub-categories, in sort order', function (): void {
        $drinks = CategoryModel::factory()->create(['name' => 'Drinks', 'sort' => 80]);
        CategoryModel::factory()->childOf($drinks)->create(['name' => 'Coffee', 'sort' => 30]);
        CategoryModel::factory()->childOf($drinks)->create(['name' => 'Milkshakes', 'sort' => 20]);
        CategoryModel::factory()->childOf($drinks)->inactive()->create(['name' => 'Beer']);
        CategoryModel::factory()->childOf($drinks)->create(['name' => 'Old Juices'])->delete();
        CategoryModel::factory()->hiddenFromMegaMenu()->create(['name' => 'Meal Deals', 'sort' => 90]);
        CategoryModel::factory()->inactive()->create(['name' => 'Closed', 'sort' => 5]);

        $menu = $this->query->megaMenu();

        expect(array_map(fn (CategoryNode $node): string => $node->name, $menu))->toBe(['Pizza', 'Drinks'])
            ->and($menu[0]->children)->toBe([])
            ->and(array_map(fn (CategoryNode $node): string => $node->name, $menu[1]->children))->toBe(['Milkshakes', 'Coffee']);
    });
});

it('needs the same number of queries for 1 card as for 10 (no N+1)', function (): void {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->query->productCardsForCategory($this->pizza->id);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    shopProduct($this->pizza);
    $forOne = $countQueries();

    ProductModel::factory()->count(9)->for($this->pizza)->create();
    $forTen = $countQueries();

    expect(count($this->query->productCardsForCategory($this->pizza->id)))->toBe(10)
        ->and($forTen)->toBe($forOne);
});

describe('pricing facts for other modules', function (): void {
    it('gives the name, the switches and the variants in sort order, without deleted variants', function (): void {
        $product = shopProduct($this->pizza, ['Personal' => [45_000, true], 'Large' => [125_000, false], 'Old' => [90_000, true]], ['name' => 'Margherita']);
        $product->variants()->where('label', 'Old')->firstOrFail()->delete();

        $facts = $this->query->pricingFacts($product->id);

        expect($facts->productId)->toBe($product->id)
            ->and($facts->name)->toBe('Margherita')
            ->and($facts->isVisible)->toBeTrue()
            ->and($facts->isAvailable)->toBeTrue()
            ->and(array_map(fn (VariantPricingFacts $variant): string => $variant->label, $facts->variants))->toBe(['Personal', 'Large'])
            ->and($facts->variants[0]->price->poisha())->toBe(45_000)
            ->and($facts->variants[1]->isAvailable)->toBeFalse();
    });

    it('says that a hidden product, or one in an inactive category, is not visible', function (): void {
        $hidden = shopProduct($this->pizza, attributes: ['is_published' => false]);
        $inactive = shopProduct(CategoryModel::factory()->inactive()->create());

        expect($this->query->pricingFacts($hidden->id)->isVisible)->toBeFalse()
            ->and($this->query->pricingFacts($inactive->id)->isVisible)->toBeFalse();
    });

    it('returns null for an unknown or deleted product', function (): void {
        $deleted = shopProduct($this->pizza);
        $deleted->delete();

        expect($this->query->pricingFacts($deleted->id))->toBeNull()
            ->and($this->query->pricingFacts(424242))->toBeNull();
    });
});
