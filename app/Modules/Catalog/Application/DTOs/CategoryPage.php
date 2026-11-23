<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * The head of a category page. A sub-category has a parent and no children.
 */
final readonly class CategoryPage
{
    /**
     * @param  list<CategoryNode>  $children  the active sub-categories
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $imagePath,
        public ?string $parentName,
        public ?string $parentSlug,
        public array $children,
    ) {}
}
