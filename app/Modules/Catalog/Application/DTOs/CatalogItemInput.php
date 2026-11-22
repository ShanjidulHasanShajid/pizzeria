<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * Input for actions that need only the id of one item: delete, restore, toggle.
 */
final readonly class CatalogItemInput
{
    public function __construct(public int $id) {}
}
