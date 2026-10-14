<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\ProductImage;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductImages;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductVariants;
use App\Modules\Catalog\Domain\Exceptions\ProductNotGiftable;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A new, unsaved product (garlic bread, 4 pcs). Change only what a test is about.
 */
function newProduct(
    ProductType $type = ProductType::Standard,
    ?Variant $firstVariant = null,
    string $name = 'Garlic Bread',
    int $categoryId = 3,
    ?string $shortDescription = null,
    ?string $description = null,
): Product {
    return Product::create(
        type: $type,
        categoryId: $categoryId,
        name: $name,
        slug: Slug::fromString('garlic-bread'),
        leadTime: LeadTime::madeToOrder(15),
        firstVariant: $firstVariant ?? Variant::create('4 pcs', Money::fromTaka('220'), SortOrder::first()),
        sort: SortOrder::first(),
        shortDescription: $shortDescription,
        description: $description,
    );
}

/**
 * A variant as it comes back from the database. Its sort follows its id: 10, 20, 30 ...
 */
function savedVariant(int $id, string $label, string $taka, ?int $sizeTierId = null, bool $isDefault = false): Variant
{
    return Variant::reconstitute($id, $label, Money::fromTaka($taka), $sizeTierId, $isDefault, true, SortOrder::fromInt($id * 10));
}

/**
 * An image as it comes back from the database. Its sort follows its id.
 */
function savedImage(int $id, string $path, bool $isMain = false): ProductImage
{
    return ProductImage::reconstitute($id, ImagePath::of($path), $isMain, SortOrder::fromInt($id * 10));
}

/**
 * A product as it comes back from the database: a Margherita with
 * Personal (default), Medium and Large, linked to size tiers 1, 2 and 3.
 *
 * @param  list<Variant>|null  $variants
 * @param  list<ProductImage>  $images
 * @param  list<int>  $tagIds
 */
function savedProduct(
    ProductType $type = ProductType::Standard,
    ?array $variants = null,
    array $images = [],
    array $tagIds = [],
    bool $isGiftable = false,
): Product {
    return Product::reconstitute(
        id: 1,
        type: $type,
        categoryId: 1,
        name: 'Margherita',
        slug: Slug::fromString('margherita'),
        shortDescription: 'Tomato, mozzarella, basil.',
        description: null,
        leadTime: LeadTime::madeToOrder(20),
        badges: ProductBadges::none(),
        isGiftable: $isGiftable,
        isPublished: true,
        isAvailable: true,
        sort: SortOrder::first(),
        variants: $variants ?? [
            savedVariant(1, 'Personal', '450', sizeTierId: 1, isDefault: true),
            savedVariant(2, 'Medium', '850', sizeTierId: 2),
            savedVariant(3, 'Large', '1250', sizeTierId: 3),
        ],
        images: $images,
        tagIds: $tagIds,
    );
}

/**
 * @return list<string> the variant labels in display order
 */
function variantLabels(Product $product): array
{
    return array_map(fn (Variant $variant): string => $variant->label(), $product->variants());
}

/**
 * @return list<string> the original image paths in display order
 */
function imagePaths(Product $product): array
{
    return array_map(fn (ProductImage $image): string => $image->path()->original(), $product->images());
}

describe('a new product', function (): void {
    it('starts unpublished and available, without badges, images or tags', function (): void {
        $product = newProduct();

        expect($product->id())->toBeNull()
            ->and($product->type())->toBe(ProductType::Standard)
            ->and($product->categoryId())->toBe(3)
            ->and($product->isPublished())->toBeFalse()
            ->and($product->isAvailable())->toBeTrue()
            ->and($product->isGiftable())->toBeFalse()
            ->and($product->badges()->hasAny())->toBeFalse()
            ->and($product->images())->toBe([])
            ->and($product->mainImage())->toBeNull()
            ->and($product->tagIds())->toBe([]);
    });

    it('makes the first variant the default', function (): void {
        $product = newProduct();

        expect(variantLabels($product))->toBe(['4 pcs'])
            ->and($product->defaultVariant()->label())->toBe('4 pcs')
            ->and($product->defaultVariant()->isDefault())->toBeTrue();
    });

    it('trims its texts and stores empty descriptions as null', function (): void {
        $product = newProduct(name: '  Garlic Bread ', shortDescription: '   ', description: ' Crispy and buttery. ');

        expect($product->name())->toBe('Garlic Bread')
            ->and($product->shortDescription())->toBeNull()
            ->and($product->description())->toBe('Crispy and buttery.');
    });

    it('rejects an empty name, a missing category and a too long short description', function (): void {
        expect(fn () => newProduct(name: ' '))->toThrow(InvalidValue::class);
        expect(fn () => newProduct(categoryId: 0))->toThrow(InvalidValue::class);
        expect(fn () => newProduct(shortDescription: str_repeat('x', 256)))->toThrow(InvalidValue::class);
    });
});

describe('a saved product', function (): void {
    it('is rebuilt with its id, variants, images and tags', function (): void {
        $product = savedProduct(
            images: [savedImage(1, 'products/margherita.webp', isMain: true), savedImage(2, 'products/margherita-2.webp')],
            tagIds: [3, 1, 3],
        );

        expect($product->id())->toBe(1)
            ->and(variantLabels($product))->toBe(['Personal', 'Medium', 'Large'])
            ->and($product->mainImage()?->path()->original())->toBe('products/margherita.webp')
            ->and($product->tagIds())->toBe([1, 3]);
    });

    it('lists variants in sort order, whatever order they were loaded in', function (): void {
        $product = savedProduct(variants: [
            savedVariant(3, 'Large', '1250', sizeTierId: 3),
            savedVariant(1, 'Personal', '450', sizeTierId: 1, isDefault: true),
            savedVariant(2, 'Medium', '850', sizeTierId: 2),
        ]);

        expect(variantLabels($product))->toBe(['Personal', 'Medium', 'Large']);
    });

    it('finds a variant by its label, ignoring case and outer spaces', function (): void {
        $product = savedProduct();

        expect($product->variant(' large ')->id())->toBe(3);
        expect(fn () => $product->variant('Family'))->toThrow(InvalidProductVariants::class);
    });

    it('changes its texts, category, lead time, badges and position', function (): void {
        $product = savedProduct();

        $product->rename('Margherita Classic');
        $product->changeSlug(Slug::fromString('margherita-classic'));
        $product->describe('Tomato and mozzarella.', 'Our first pizza.');
        $product->moveToCategory(2);
        $product->changeLeadTime(LeadTime::madeToOrder(25));
        $product->changeBadges(ProductBadges::of(isNew: true, isHot: false));
        $product->moveTo(SortOrder::fromInt(40));

        expect($product->name())->toBe('Margherita Classic')
            ->and($product->slug()->value())->toBe('margherita-classic')
            ->and($product->shortDescription())->toBe('Tomato and mozzarella.')
            ->and($product->description())->toBe('Our first pizza.')
            ->and($product->categoryId())->toBe(2)
            ->and($product->leadTime()->minutes())->toBe(25)
            ->and($product->badges()->isNew())->toBeTrue()
            ->and($product->sort()->value())->toBe(40);
    });
});

describe('rules', function (): void {
    it('rejects a product without variants', function (): void {
        expect(fn () => savedProduct(variants: []))
            ->toThrow(InvalidProductVariants::class, 'at least one variant');
    });

    it('needs exactly one default variant', function (): void {
        expect(fn () => savedProduct(variants: [
            savedVariant(1, 'Regular', '300'),
            savedVariant(2, 'Large', '450'),
        ]))->toThrow(InvalidProductVariants::class, 'Exactly one variant');

        expect(fn () => savedProduct(variants: [
            savedVariant(1, 'Regular', '300', isDefault: true),
            savedVariant(2, 'Large', '450', isDefault: true),
        ]))->toThrow(InvalidProductVariants::class, 'Exactly one variant');
    });

    it('rejects two variants with the same label, ignoring case', function (): void {
        expect(fn () => savedProduct(variants: [
            savedVariant(1, 'Large', '1250', isDefault: true),
            savedVariant(2, ' large', '1300'),
        ]))->toThrow(InvalidProductVariants::class, 'already a variant');
    });

    it('gives a combo exactly one variant: the bundle price', function (): void {
        $combo = savedProduct(ProductType::Combo, variants: [savedVariant(1, 'Bundle', '1499', isDefault: true)]);

        expect(variantLabels($combo))->toBe(['Bundle']);

        expect(fn () => savedProduct(ProductType::Combo, variants: [
            savedVariant(1, 'Bundle', '1499', isDefault: true),
            savedVariant(2, 'Big bundle', '1999'),
        ]))->toThrow(InvalidProductVariants::class, 'bundle price');
    });

    it('needs a size on every variant of a build-your-own pizza', function (): void {
        $noSize = Variant::create('Personal', Money::fromTaka('400'), SortOrder::first());

        expect(fn () => newProduct(ProductType::Builder, firstVariant: $noSize))
            ->toThrow(InvalidProductVariants::class, 'Choose a size');

        expect(fn () => savedProduct(ProductType::Builder, variants: [
            savedVariant(1, 'Personal', '400', sizeTierId: 1, isDefault: true),
            savedVariant(2, 'Medium', '700'),
        ]))->toThrow(InvalidProductVariants::class, 'Choose a size');
    });

    it('allows each size only once on a build-your-own pizza', function (): void {
        expect(fn () => savedProduct(ProductType::Builder, variants: [
            savedVariant(1, 'Personal', '400', sizeTierId: 1, isDefault: true),
            savedVariant(2, 'Small', '380', sizeTierId: 1),
        ]))->toThrow(InvalidProductVariants::class, 'only one variant per size');

        expect(savedProduct(ProductType::Builder)->variants())->toHaveCount(3);
    });

    it('lets a standard product have variants without a size', function (): void {
        $wings = savedProduct(variants: [
            savedVariant(1, '6 pcs', '390', isDefault: true),
            savedVariant(2, '12 pcs', '720'),
        ]);

        expect(variantLabels($wings))->toBe(['6 pcs', '12 pcs']);
    });

    it('needs exactly one main image when there are images', function (): void {
        expect(fn () => savedProduct(images: [
            savedImage(1, 'products/a.webp'),
            savedImage(2, 'products/b.webp'),
        ]))->toThrow(InvalidProductImages::class, 'main image');

        expect(fn () => savedProduct(images: [
            savedImage(1, 'products/a.webp', isMain: true),
            savedImage(2, 'products/b.webp', isMain: true),
        ]))->toThrow(InvalidProductImages::class, 'main image');

        expect(savedProduct(images: [])->mainImage())->toBeNull();
    });

    it('rejects the same image twice', function (): void {
        expect(fn () => savedProduct(images: [
            savedImage(1, 'products/a.webp', isMain: true),
            savedImage(2, 'products/a.webp'),
        ]))->toThrow(InvalidProductImages::class, 'already on this product');
    });

    it('lets only a combo be a gift box', function (): void {
        expect(fn () => savedProduct(isGiftable: true))->toThrow(ProductNotGiftable::class);

        $giftBox = savedProduct(ProductType::Combo, variants: [savedVariant(1, 'Box', '2500', isDefault: true)], isGiftable: true);

        expect($giftBox->isGiftable())->toBeTrue();
    });
});

describe('publishing and availability', function (): void {
    it('can be published and unpublished', function (): void {
        $product = newProduct();

        $product->publish();
        expect($product->isPublished())->toBeTrue();

        $product->unpublish();
        expect($product->isPublished())->toBeFalse();
    });

    it('can be sold out today and available again', function (): void {
        $product = savedProduct();

        $product->markSoldOut();
        expect($product->isAvailable())->toBeFalse()
            ->and($product->isPublished())->toBeTrue();

        $product->markAvailable();
        expect($product->isAvailable())->toBeTrue();
    });

    it('turns a combo into a gift box and back', function (): void {
        $combo = newProduct(ProductType::Combo, firstVariant: Variant::create('Bundle', Money::fromTaka('1499'), SortOrder::first()));

        $combo->markGiftable();
        expect($combo->isGiftable())->toBeTrue();

        $combo->unmarkGiftable();
        expect($combo->isGiftable())->toBeFalse();
    });

    it('refuses to make a standard product a gift box', function (): void {
        $product = newProduct();

        expect(fn () => $product->markGiftable())->toThrow(ProductNotGiftable::class, 'Only combos');
        expect($product->isGiftable())->toBeFalse();
    });
});

describe('variants', function (): void {
    it('adds a variant that is not the default', function (): void {
        $product = savedProduct();

        $product->addVariant(Variant::create('Family', Money::fromTaka('1650'), SortOrder::fromInt(40), sizeTierId: 4));

        expect(variantLabels($product))->toBe(['Personal', 'Medium', 'Large', 'Family'])
            ->and($product->variant('Family')->isDefault())->toBeFalse()
            ->and($product->defaultVariant()->label())->toBe('Personal');
    });

    it('refuses a duplicate label and keeps the variants as they were', function (): void {
        $product = savedProduct();

        expect(fn () => $product->addVariant(Variant::create(' medium ', Money::fromTaka('900'), SortOrder::fromInt(40))))
            ->toThrow(InvalidProductVariants::class, 'already a variant');
        expect(variantLabels($product))->toBe(['Personal', 'Medium', 'Large']);
    });

    it('refuses a second variant on a combo', function (): void {
        $combo = savedProduct(ProductType::Combo, variants: [savedVariant(1, 'Bundle', '1499', isDefault: true)]);

        expect(fn () => $combo->addVariant(Variant::create('Big bundle', Money::fromTaka('1999'), SortOrder::fromInt(20))))
            ->toThrow(InvalidProductVariants::class, 'bundle price');
        expect(variantLabels($combo))->toBe(['Bundle']);
    });

    it('updates the label, price and size of a variant', function (): void {
        $product = savedProduct();

        $product->updateVariant('Medium', 'Regular', Money::fromTaka('899'), 2);

        $regular = $product->variant('Regular');

        expect($regular->id())->toBe(2)
            ->and($regular->price()->poisha())->toBe(89_900)
            ->and($regular->sizeTierId())->toBe(2)
            ->and(variantLabels($product))->toBe(['Personal', 'Regular', 'Large']);
    });

    it('leaves the variant untouched when an update breaks a rule', function (): void {
        $product = savedProduct(ProductType::Builder);

        expect(fn () => $product->updateVariant('Medium', 'Large', Money::fromTaka('999'), 2))
            ->toThrow(InvalidProductVariants::class, 'already a variant');
        expect(fn () => $product->updateVariant('Medium', 'Medium', Money::fromTaka('999'), null))
            ->toThrow(InvalidProductVariants::class, 'Choose a size');

        expect($product->variant('Medium')->price()->poisha())->toBe(85_000)
            ->and($product->variant('Medium')->sizeTierId())->toBe(2);
    });

    it('removes a variant and gives the default to the first remaining one', function (): void {
        $product = savedProduct();

        $product->removeVariant('Personal');

        expect(variantLabels($product))->toBe(['Medium', 'Large'])
            ->and($product->defaultVariant()->label())->toBe('Medium');
    });

    it('never removes the last variant', function (): void {
        $combo = savedProduct(ProductType::Combo, variants: [savedVariant(1, 'Bundle', '1499', isDefault: true)]);

        expect(fn () => $combo->removeVariant('Bundle'))
            ->toThrow(InvalidProductVariants::class, 'last one');
    });

    it('switches the default variant', function (): void {
        $product = savedProduct();

        $product->setDefaultVariant('Large');

        $defaults = array_filter($product->variants(), fn (Variant $variant): bool => $variant->isDefault());

        expect($product->defaultVariant()->label())->toBe('Large')
            ->and($defaults)->toHaveCount(1);
    });

    it('marks one variant sold out and available again', function (): void {
        $product = savedProduct();

        $product->markVariantSoldOut('Large');

        expect($product->variant('Large')->isAvailable())->toBeFalse()
            ->and($product->variant('Medium')->isAvailable())->toBeTrue();

        $product->markVariantAvailable('Large');

        expect($product->variant('Large')->isAvailable())->toBeTrue();
    });
});

describe('images and tags', function (): void {
    it('makes the first image the main one and adds the others at the end', function (): void {
        $product = savedProduct();

        $product->addImage(ImagePath::of('products/a.webp'));
        $product->addImage(ImagePath::of('products/b.webp'));

        expect(imagePaths($product))->toBe(['products/a.webp', 'products/b.webp'])
            ->and($product->mainImage()?->path()->original())->toBe('products/a.webp')
            ->and($product->image('products/b.webp')->sort()->value())->toBe(20);
    });

    it('refuses the same image twice', function (): void {
        $product = savedProduct();
        $product->addImage(ImagePath::of('products/a.webp'));

        expect(fn () => $product->addImage(ImagePath::of('products/a.webp')))
            ->toThrow(InvalidProductImages::class, 'already on this product');
        expect($product->images())->toHaveCount(1);
    });

    it('returns the paths of a removed image and moves the main image to the next one', function (): void {
        $product = savedProduct();
        $product->addImage(ImagePath::of('products/a.webp', 'products/thumbs/a.webp'));
        $product->addImage(ImagePath::of('products/b.webp'));

        $removed = $product->removeImage('products/a.webp');

        expect($removed->thumbnail())->toBe('products/thumbs/a.webp')
            ->and(imagePaths($product))->toBe(['products/b.webp'])
            ->and($product->mainImage()?->path()->original())->toBe('products/b.webp');
    });

    it('sets another image as the main one', function (): void {
        $product = savedProduct(images: [
            savedImage(1, 'products/a.webp', isMain: true),
            savedImage(2, 'products/b.webp'),
        ]);

        $product->setMainImage('products/b.webp');

        expect($product->mainImage()?->path()->original())->toBe('products/b.webp')
            ->and($product->image('products/a.webp')->isMain())->toBeFalse();
    });

    it('puts the images in a new order', function (): void {
        $product = savedProduct(images: [
            savedImage(1, 'products/a.webp', isMain: true),
            savedImage(2, 'products/b.webp'),
            savedImage(3, 'products/c.webp'),
        ]);

        $product->reorderImages(['products/c.webp', 'products/a.webp', 'products/b.webp']);

        expect(imagePaths($product))->toBe(['products/c.webp', 'products/a.webp', 'products/b.webp'])
            ->and($product->image('products/c.webp')->sort()->value())->toBe(10)
            ->and($product->mainImage()?->path()->original())->toBe('products/a.webp');
    });

    it('refuses a new order that misses or repeats an image', function (): void {
        $product = savedProduct(images: [
            savedImage(1, 'products/a.webp', isMain: true),
            savedImage(2, 'products/b.webp'),
        ]);

        expect(fn () => $product->reorderImages(['products/a.webp']))
            ->toThrow(InvalidProductImages::class);
        expect(fn () => $product->reorderImages(['products/a.webp', 'products/a.webp']))
            ->toThrow(InvalidProductImages::class);
    });

    it('assigns tags without duplicates', function (): void {
        $product = savedProduct(tagIds: [1]);

        $product->assignTags([5, 2, 5]);
        expect($product->tagIds())->toBe([2, 5]);

        $product->assignTags([]);
        expect($product->tagIds())->toBe([]);
    });

    it('rejects an invalid tag id', function (): void {
        expect(fn () => savedProduct()->assignTags([0]))->toThrow(InvalidValue::class);
    });
});
