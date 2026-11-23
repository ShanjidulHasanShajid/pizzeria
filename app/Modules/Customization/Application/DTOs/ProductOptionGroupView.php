<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * One option group as a product offers it: the group's texts, this product's rules, and the options.
 */
final readonly class ProductOptionGroupView
{
    /**
     * @param  list<OptionView>  $options  in sort order; sold-out ones included and marked
     */
    public function __construct(
        public int $optionGroupId,
        public string $title,
        public ?string $description,
        public bool $isRequired,
        public int $minimum,
        public int $maximum,
        public int $freeCount,
        public array $options,
    ) {}
}
