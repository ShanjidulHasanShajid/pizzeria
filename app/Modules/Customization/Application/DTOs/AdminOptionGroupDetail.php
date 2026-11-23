<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * One option group for the edit page, the option manager and the price matrix.
 */
final readonly class AdminOptionGroupDetail
{
    /**
     * @param  list<AdminOptionRow>  $options  in sort order
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $displayTitle,
        public ?string $description,
        public bool $isActive,
        public array $options,
    ) {}
}
