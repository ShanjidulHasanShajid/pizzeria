<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * The ids of a list in their new order, as the admin dragged them.
 */
final readonly class ReorderInput
{
    /**
     * @param  list<int>  $ids
     */
    public function __construct(public array $ids) {}
}
