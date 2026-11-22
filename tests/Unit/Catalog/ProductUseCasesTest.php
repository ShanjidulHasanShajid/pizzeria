<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\ImageUpload;
use App\Modules\Catalog\Application\DTOs\ProductFormInput;
use App\Modules\Catalog\Application\DTOs\ProductImageInput;
use App\Modules\Catalog\Application\DTOs\ReorderProductImagesInput;
use App\Modules\Catalog\Application\DTOs\UpdateProductInput;
use App\Modules\Catalog\Application\DTOs\UploadProductImagesInput;
use App\Modules\Catalog\Application\DTOs\VariantInput;
use App\Modules\Catalog\Application\Services\CatalogImages;
use App\Modules\Catalog\Application\Services\ProductInputMapper;
use App\Modules\Catalog\Application\UseCases\Products\CreateProduct;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProduct;
use App\Modules\Catalog\Application\UseCases\Products\DeleteProductImage;
use App\Modules\Catalog\Application\UseCases\Products\DuplicateProduct;
use App\Modules\Catalog\Application\UseCases\Products\ForceDeleteProduct;
use App\Modules\Catalog\Application\UseCases\Products\ReorderProductImages;
use App\Modules\Catalog\Application\UseCases\Products\RestoreProduct;
use App\Modules\Catalog\Application\UseCases\Products\SetMainProductImage;
use App\Modules\Catalog\Application\UseCases\Products\ToggleProductAvailability;
use App\Modules\Catalog\Application\UseCases\Products\ToggleProductPublished;
use App\Modules\Catalog\Application\UseCases\Products\UpdateProduct;
use App\Modules\Catalog\Application\UseCases\Products\UploadProductImages;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Entities\ProductImage;
use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Catalog\Fakes\InMemoryCategoryRepository;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\InMemoryProductRepository;
use Tests\Unit\Catalog\Fakes\InMemorySizeTierRepository;
use Tests\Unit\Catalog\Fakes\InMemoryTagRepository;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Identity\PassThroughTransactionManager;

/**
 * One variant row as the form sends it.
 */
function formVariant(string $label, string $taka, ?int $id = null, ?int $sizeTierId = null, bool $isDefault = false, bool $isAvailable = true): VariantInput
{
    return new VariantInput($id, $label, Money::fromTaka($taka), $sizeTierId, $isDefault, $isAvailable);
}

/**
 * A product form with sensible defaults: a published Margherita, Personal (default) and Large.
 *
 * @param  array<string, mixed>  $changes  named constructor arguments
 */
function productForm(array $changes = []): ProductFormInput
{
    return new ProductFormInput(...[
        'categoryId' => 1,
        'name' => 'Margherita',
        'slug' => null,
        'shortDescription' => 'Tomato and mozzarella.',
        'description' => null,
        'leadTimeClass' => LeadTimeClass::MadeToOrder,
        'leadTimeMinutes' => 20,
        'isNew' => false,
        'isHot' => false,
        'isPublished' => true,
        'isAvailable' => true,
        'variants' => [formVariant('Personal', '450', sizeTierId: 1, isDefault: true), formVariant('Large', '1250', sizeTierId: 2)],
        'tagIds' => [],
        ...$changes,
    ]);
}

/**
 * @return list<string> the variant labels of a saved product, in display order
 */
function savedVariantLabels(InMemoryProductRepository $products, int $id): array
{
    return array_map(fn (Variant $variant): string => $variant->label(), $products->find($id)->variants());
}

beforeEach(function (): void {
    $this->products = new InMemoryProductRepository;
    $categories = new InMemoryCategoryRepository;
    $categories->save(Category::create('Pizza', Slug::fromString('pizza'), 1, SortOrder::first()));        // id 1
    $categories->save(Category::create('Sides', Slug::fromString('sides'), 1, SortOrder::fromInt(20)));    // id 2
    $sizeTiers = new InMemorySizeTierRepository;
    $sizeTiers->save(SizeTier::create('Personal', Slug::fromString('personal'), SortOrder::first()));     // id 1
    $sizeTiers->save(SizeTier::create('Large', Slug::fromString('large'), SortOrder::fromInt(20)));       // id 2
    $tags = new InMemoryTagRepository;
    $tags->save(Tag::create('Veg', Slug::fromString('veg'), TagType::Dietary, SortOrder::first()));       // id 1
    $this->mapper = new ProductInputMapper($categories, $sizeTiers, $tags);
    $this->files = new InMemoryFileStorage;
    $this->images = new CatalogImages($this->files, new RecordingImageProcessor);
    $this->transactions = new PassThroughTransactionManager;

    $this->create = fn (array $changes = []): int => (new CreateProduct($this->products, $this->mapper, new SlugGenerator, $this->transactions))
        ->handle(productForm($changes));
    $this->update = fn (int $id, array $changes = []) => (new UpdateProduct($this->products, $this->mapper, $this->transactions))
        ->handle(new UpdateProductInput($id, productForm($changes)));
    $this->upload = fn (int $id, string ...$contents) => (new UploadProductImages($this->products, $this->images))
        ->handle(new UploadProductImagesInput($id, array_map(fn (string $data): ImageUpload => new ImageUpload($data), $contents)));
});

describe('creating', function (): void {
    it('creates a product with its variants in order, the chosen default, sold-out rows and tags', function (): void {
        $id = ($this->create)([
            'variants' => [
                formVariant('Personal', '450', sizeTierId: 1),
                formVariant('Large', '1250', sizeTierId: 2, isDefault: true, isAvailable: false),
            ],
            'tagIds' => [1],
            'isHot' => true,
        ]);

        $product = $this->products->find($id);

        expect($product->type())->toBe(ProductType::Standard)
            ->and($product->slug()->value())->toBe('margherita')
            ->and(savedVariantLabels($this->products, $id))->toBe(['Personal', 'Large'])
            ->and($product->defaultVariant()->label())->toBe('Large')
            ->and($product->variant('Large')->isAvailable())->toBeFalse()
            ->and($product->variant('Large')->price()->poisha())->toBe(125_000)
            ->and($product->tagIds())->toBe([1])
            ->and($product->badges()->isHot())->toBeTrue()
            ->and($product->isPublished())->toBeTrue();
    });

    it('refuses an unknown category, size or tag, under the right field', function (string $field, array $changes): void {
        try {
            ($this->create)($changes);
            $this->fail('An unknown id was accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey($field);
        }
    })->with([
        'category' => ['category_id', ['categoryId' => 99]],
        'size' => ['variants.0.size_tier_id', ['variants' => [formVariant('Personal', '450', sizeTierId: 99, isDefault: true)]]],
        'tag' => ['tags', ['tagIds' => [99]]],
    ]);

    it('turns a broken variant rule into an error for the variants field', function (): void {
        try {
            ($this->create)(['variants' => [formVariant('Large', '1250', isDefault: true), formVariant('large', '1300')]]);
            $this->fail('Two variants with the same label were accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('variants');
        }
    });

    it('refuses a typed slug that is already taken', function (): void {
        ($this->create)();

        ($this->create)(['name' => 'Margherita Classic', 'slug' => 'margherita']);
    })->throws(ValidationFailed::class);
});

describe('updating', function (): void {
    it('changes, adds, removes and reorders variants, keeping the ids of edited rows', function (): void {
        $id = ($this->create)(); // Personal (variant 1, default), Large (variant 2)

        ($this->update)($id, ['variants' => [
            formVariant('Large', '1300', id: 2, sizeTierId: 2, isDefault: true),
            formVariant('Family', '1700'),
        ]]);

        $product = $this->products->find($id);

        expect(savedVariantLabels($this->products, $id))->toBe(['Large', 'Family'])
            ->and($product->variant('Large')->id())->toBe(2)
            ->and($product->variant('Large')->price()->poisha())->toBe(130_000)
            ->and($product->variant('Family')->id())->toBe(3)
            ->and($product->defaultVariant()->label())->toBe('Large');
    });

    it('updates details, category, tags, timing and visibility', function (): void {
        $id = ($this->create)();

        ($this->update)($id, [
            'categoryId' => 2,
            'name' => 'Margherita Classic',
            'leadTimeClass' => LeadTimeClass::SameDay,
            'leadTimeMinutes' => 180,
            'isNew' => true,
            'isPublished' => false,
            'isAvailable' => false,
            'tagIds' => [1],
            'variants' => [formVariant('Personal', '450', id: 1, sizeTierId: 1, isDefault: true), formVariant('Large', '1250', id: 2, sizeTierId: 2)],
        ]);

        $product = $this->products->find($id);

        expect($product->name())->toBe('Margherita Classic')
            ->and($product->slug()->value())->toBe('margherita')
            ->and($product->categoryId())->toBe(2)
            ->and($product->leadTime()->leadTimeClass())->toBe(LeadTimeClass::SameDay)
            ->and($product->badges()->isNew())->toBeTrue()
            ->and($product->isPublished())->toBeFalse()
            ->and($product->isAvailable())->toBeFalse()
            ->and($product->tagIds())->toBe([1]);
    });

    it('refuses a variant that belongs to another product', function (): void {
        $margherita = ($this->create)();
        ($this->create)(['name' => 'Pepperoni']); // variants 3 and 4

        try {
            ($this->update)($margherita, ['variants' => [formVariant('Personal', '450', id: 3, isDefault: true)]]);
            $this->fail('A variant of another product was accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('variants');
        }

        expect(savedVariantLabels($this->products, $margherita))->toBe(['Personal', 'Large']);
    });

    it('reports an unknown product', function (): void {
        ($this->update)(99);
    })->throws(EntityNotFound::class);
});

describe('switches, trash and duplicate', function (): void {
    it('switches published and sold out from the list', function (): void {
        $id = ($this->create)();

        expect((new ToggleProductPublished($this->products))->handle(new CatalogItemInput($id)))->toBeFalse()
            ->and((new ToggleProductAvailability($this->products))->handle(new CatalogItemInput($id)))->toBeFalse()
            ->and($this->products->find($id)->isPublished())->toBeFalse()
            ->and($this->products->find($id)->isAvailable())->toBeFalse();
    });

    it('moves a product to the trash and brings it back', function (): void {
        $id = ($this->create)();

        (new DeleteProduct($this->products))->handle(new CatalogItemInput($id));
        expect($this->products->find($id))->toBeNull();

        (new RestoreProduct($this->products))->handle(new CatalogItemInput($id));
        expect($this->products->find($id))->not->toBeNull();
    });

    it('deletes a product forever only from the trash, with all its image files', function (): void {
        $id = ($this->create)();
        ($this->upload)($id, 'front', 'side');
        $forceDelete = new ForceDeleteProduct($this->products, $this->images);

        expect(fn () => $forceDelete->handle(new CatalogItemInput($id)))->toThrow(EntityNotFound::class);

        $this->products->delete($id);
        $forceDelete->handle(new CatalogItemInput($id));

        expect($this->products->exists($id))->toBeFalse()
            ->and($this->files->paths())->toBe([]);
    });

    it('duplicates a product as an unpublished copy with new variants and no images', function (): void {
        $id = ($this->create)(['tagIds' => [1], 'variants' => [
            formVariant('Personal', '450', sizeTierId: 1),
            formVariant('Large', '1250', sizeTierId: 2, isDefault: true),
        ]]);
        ($this->upload)($id, 'photo');

        $copyId = (new DuplicateProduct($this->products, new SlugGenerator))->handle(new CatalogItemInput($id));
        $copy = $this->products->find($copyId);

        expect($copy->name())->toBe('Margherita (copy)')
            ->and($copy->slug()->value())->toBe('margherita-copy')
            ->and($copy->isPublished())->toBeFalse()
            ->and(savedVariantLabels($this->products, $copyId))->toBe(['Personal', 'Large'])
            ->and($copy->defaultVariant()->label())->toBe('Large')
            ->and($copy->variant('Personal')->id())->not->toBe($this->products->find($id)->variant('Personal')->id())
            ->and($copy->tagIds())->toBe([1])
            ->and($copy->images())->toBe([]);
    });
});

describe('images', function (): void {
    it('uploads images at the end and makes the first one the main image', function (): void {
        $id = ($this->create)();

        ($this->upload)($id, 'front', 'side');

        $images = $this->products->find($id)->images();

        expect(array_map(fn (ProductImage $image): string => $image->path()->original(), $images))->toBe(['products/file-1.webp', 'products/file-3.webp'])
            ->and($images[0]->isMain())->toBeTrue()
            ->and($images[0]->path()->thumbnail())->toBe('products/thumbs/file-2.webp')
            ->and($this->files->paths())->toHaveCount(4);
    });

    it('allows at most 10 images per product', function (): void {
        $id = ($this->create)();

        ($this->upload)($id, ...array_fill(0, 11, 'photo'));
    })->throws(ValidationFailed::class);

    it('deletes the new files again when saving fails', function (): void {
        $id = ($this->create)();
        $this->products->failNextSave();

        expect(fn () => ($this->upload)($id, 'front'))->toThrow(RuntimeException::class);

        expect($this->files->paths())->toBe([]);
    });

    it('sets the main image, reorders, and deletes an image with its files', function (): void {
        $id = ($this->create)();
        ($this->upload)($id, 'a', 'b', 'c'); // products/file-1, file-3, file-5 (thumbnails 2, 4, 6)

        (new SetMainProductImage($this->products))->handle(new ProductImageInput($id, 'products/file-3.webp'));
        (new ReorderProductImages($this->products))->handle(new ReorderProductImagesInput($id, ['products/file-5.webp', 'products/file-3.webp', 'products/file-1.webp']));
        (new DeleteProductImage($this->products, $this->images))->handle(new ProductImageInput($id, 'products/file-1.webp'));

        $product = $this->products->find($id);

        expect(array_map(fn (ProductImage $image): string => $image->path()->original(), $product->images()))->toBe(['products/file-5.webp', 'products/file-3.webp'])
            ->and($product->mainImage()?->path()->original())->toBe('products/file-3.webp')
            ->and($this->files->has('products/file-1.webp'))->toBeFalse()
            ->and($this->files->has('products/thumbs/file-2.webp'))->toBeFalse()
            ->and($this->files->paths())->toHaveCount(4);
    });

    it('refuses an image the product does not have, under the uploads field', function (): void {
        $id = ($this->create)();

        try {
            (new SetMainProductImage($this->products))->handle(new ProductImageInput($id, 'products/other.webp'));
            $this->fail('An unknown image was accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('uploads');
        }
    });
});
