<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;

/**
 * Everything the product form sends (except images, which are managed on their own).
 * Used by CreateProduct, and inside UpdateProductInput.
 */
final readonly class ProductFormInput
{
    /**
     * @param  ?string  $slug  null or empty: made from the name (create) or kept (update)
     * @param  list<VariantInput>  $variants  in display order; exactly one is the default
     * @param  list<int>  $tagIds
     */
    public function __construct(
        public int $categoryId,
        public string $name,
        public ?string $slug,
        public ?string $shortDescription,
        public ?string $description,
        public LeadTimeClass $leadTimeClass,
        public int $leadTimeMinutes,
        public bool $isNew,
        public bool $isHot,
        public bool $isPublished,
        public bool $isAvailable,
        public array $variants,
        public array $tagIds,
    ) {}
}
