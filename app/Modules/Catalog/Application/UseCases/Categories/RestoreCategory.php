<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\CategoryRepository;

/**
 * Brings a category back from the Trash, with its image and its old position.
 * A sub-category whose parent is still in the Trash comes back too; the list shows
 * it at the end, and the shop hides it until the parent is back or it is moved.
 */
final class RestoreCategory
{
    public function __construct(private readonly CategoryRepository $categories) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->categories->restore($input->id);
    }
}
