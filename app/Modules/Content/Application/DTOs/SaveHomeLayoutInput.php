<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

final readonly class SaveHomeLayoutInput
{
    /**
     * @param  list<int>  $tabCategoryIds
     * @param  array<int, array{categoryId: int, productIds: list<int>}>  $rows  position on the form => row
     */
    public function __construct(
        public array $tabCategoryIds,
        public array $rows,
    ) {}
}
