<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * One line of the admin list of option groups.
 */
final readonly class OptionGroupRow
{
    /**
     * @param  int  $productCount  products that have the group (also in the Trash); above 0 blocks deleting
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $displayTitle,
        public bool $isActive,
        public int $optionCount,
        public int $soldOutCount,
        public int $productCount,
    ) {}
}
