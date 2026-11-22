<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Fakes;

use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\ProductImage;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use RuntimeException;

/**
 * A ProductRepository in an array, for use case tests. Like the database it gives new
 * variants and images an id on save(), and hands out a deep copy on find(): changing a
 * loaded product without saving it changes nothing here.
 */
final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<int, Product> */
    private array $products = [];

    /** @var array<int, true> */
    private array $deleted = [];

    private int $nextId = 1;

    private int $nextVariantId = 1;

    private int $nextImageId = 1;

    private bool $failNextSave = false;

    public function find(int $id): ?Product
    {
        if (! isset($this->products[$id]) || isset($this->deleted[$id])) {
            return null;
        }

        return $this->copy($this->products[$id], $id);
    }

    public function findByIds(array $ids): array
    {
        $found = [];

        foreach ($ids as $id) {
            $product = $this->find($id);

            if ($product !== null) {
                $found[] = $product;
            }
        }

        return $found;
    }

    public function findTrashed(int $id): ?Product
    {
        return isset($this->deleted[$id]) ? $this->copy($this->products[$id], $id) : null;
    }

    public function save(Product $product): int
    {
        if ($this->failNextSave) {
            $this->failNextSave = false;

            throw new RuntimeException('The database is down.');
        }

        $id = $product->id() ?? $this->nextId++;
        $this->products[$id] = $this->copy($product, $id, assignIds: true);

        return $id;
    }

    public function delete(int $id): void
    {
        if ($this->find($id) === null) {
            throw EntityNotFound::withId('Product', $id);
        }

        $this->deleted[$id] = true;
    }

    public function restore(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Product', $id);
        }

        unset($this->deleted[$id]);
    }

    public function forceDelete(int $id): void
    {
        if (! isset($this->deleted[$id])) {
            throw EntityNotFound::withId('Product', $id);
        }

        unset($this->products[$id], $this->deleted[$id]);
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        foreach ($this->products as $id => $product) {
            if ($id !== $ignoreId && $product->slug()->value() === $slug->value()) {
                return true;
            }
        }

        return false;
    }

    // Test helpers (not part of the interface)

    public function failNextSave(): void
    {
        $this->failNextSave = true;
    }

    public function exists(int $id): bool
    {
        return isset($this->products[$id]);
    }

    /**
     * A deep copy, rebuilt through reconstitute() like the mapper does.
     */
    private function copy(Product $product, int $id, bool $assignIds = false): Product
    {
        return Product::reconstitute(
            id: $id,
            type: $product->type(),
            categoryId: $product->categoryId(),
            name: $product->name(),
            slug: $product->slug(),
            shortDescription: $product->shortDescription(),
            description: $product->description(),
            leadTime: $product->leadTime(),
            badges: $product->badges(),
            isGiftable: $product->isGiftable(),
            isPublished: $product->isPublished(),
            isAvailable: $product->isAvailable(),
            sort: $product->sort(),
            variants: array_map(fn (Variant $variant): Variant => Variant::reconstitute(
                $variant->id() ?? ($assignIds ? $this->nextVariantId++ : 0),
                $variant->label(),
                $variant->price(),
                $variant->sizeTierId(),
                $variant->isDefault(),
                $variant->isAvailable(),
                $variant->sort(),
            ), $product->variants()),
            images: array_map(fn (ProductImage $image): ProductImage => ProductImage::reconstitute(
                $image->id() ?? ($assignIds ? $this->nextImageId++ : 0),
                $image->path(),
                $image->isMain(),
                $image->sort(),
            ), $product->images()),
            tagIds: $product->tagIds(),
        );
    }
}
