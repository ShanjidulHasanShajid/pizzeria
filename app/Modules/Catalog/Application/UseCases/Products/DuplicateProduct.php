<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * "Duplicate" (7.30): a new, UNPUBLISHED copy called "<name> (copy)" with the same category,
 * texts, lead time, badges, variants and tags, and a new slug. Images are not copied
 * (the copy usually gets its own photos). Returns the new id, so the admin can edit the copy.
 */
final class DuplicateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly SlugGenerator $slugs,
    ) {}

    public function handle(CatalogItemInput $input): int
    {
        $original = $this->products->find($input->id)
            ?? throw EntityNotFound::withId('Product', $input->id);

        $name = mb_substr($original->name(), 0, Product::NAME_MAX_LENGTH - 7).' (copy)';

        $variants = array_map(function (Variant $variant): Variant {
            $copy = Variant::create($variant->label(), $variant->price(), SortOrder::first(), $variant->sizeTierId());

            if ($variant->isDefault()) {
                $copy->markAsDefault();
            }

            if (! $variant->isAvailable()) {
                $copy->markSoldOut();
            }

            return $copy;
        }, $original->variants());

        $copy = Product::create(
            type: $original->type(),
            categoryId: $original->categoryId(),
            name: $name,
            slug: $this->slugs->generate($name, fn (Slug $slug): bool => $this->products->slugExists($slug)),
            leadTime: $original->leadTime(),
            firstVariant: clone $variants[0], // create() marks it default; the real list follows
            sort: $original->sort(),
            shortDescription: $original->shortDescription(),
            description: $original->description(),
        );
        $copy->replaceVariants($variants);
        $copy->changeBadges($original->badges());
        $copy->assignTags($original->tagIds());

        if (! $original->isAvailable()) {
            $copy->markSoldOut();
        }

        return $this->products->save($copy);
    }
}
