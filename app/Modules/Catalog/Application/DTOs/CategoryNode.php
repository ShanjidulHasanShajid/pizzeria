<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * A category in the mega menu: a top-level category with its sub-categories as children.
 * Sub-categories have no children (only one level is allowed).
 */
final readonly class CategoryNode
{
    /**
     * @param  list<CategoryNode>  $children
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $imagePath,
        public array $children,
    ) {}
}
