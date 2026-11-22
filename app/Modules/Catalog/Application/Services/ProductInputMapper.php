<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Services;

use App\Modules\Catalog\Application\DTOs\ProductFormInput;
use App\Modules\Catalog\Domain\Entities\Variant;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The part of saving a product form that CreateProduct and UpdateProduct share:
 * - check that the chosen category, sizes and tags really exist (not deleted),
 * - turn the variant rows into Variant entities.
 * Errors name the form field, so the admin sees them in the right place.
 */
final class ProductInputMapper
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly SizeTierRepository $sizeTiers,
        private readonly TagRepository $tags,
    ) {}

    public function assertReferencesExist(ProductFormInput $form): void
    {
        if ($this->categories->find($form->categoryId) === null) {
            throw ValidationFailed::forField('category_id', 'Choose an existing category.');
        }

        foreach ($form->variants as $index => $row) {
            if ($row->sizeTierId !== null && $this->sizeTiers->find($row->sizeTierId) === null) {
                throw ValidationFailed::forField("variants.{$index}.size_tier_id", 'Choose an existing size.');
            }
        }

        foreach ($form->tagIds as $tagId) {
            if ($this->tags->find($tagId) === null) {
                throw ValidationFailed::forField('tags', 'Choose existing tags only.');
            }
        }
    }

    /**
     * Rows with an id become the saved variant with new values (same row in the database);
     * rows without an id become new variants. The sort is set by Product::replaceVariants().
     *
     * @return list<Variant>
     */
    public function variants(ProductFormInput $form): array
    {
        $variants = [];

        foreach ($form->variants as $row) {
            $variant = $row->id === null
                ? Variant::create($row->label, $row->price, SortOrder::first(), $row->sizeTierId)
                : Variant::reconstitute($row->id, $row->label, $row->price, $row->sizeTierId, false, true, SortOrder::first());

            if ($row->isDefault) {
                $variant->markAsDefault();
            }

            if (! $row->isAvailable) {
                $variant->markSoldOut();
            }

            $variants[] = $variant;
        }

        return $variants;
    }
}
