<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;

/**
 * One row of the admin product table.
 */
final readonly class AdminProductRow
{
    /**
     * @param  Money  $lowestPrice  cheapest variant that is not deleted
     * @param  Money  $highestPrice  most expensive variant that is not deleted
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ProductType $type,
        public string $categoryName,
        public ?string $imagePath,
        public Money $lowestPrice,
        public Money $highestPrice,
        public int $variantCount,
        public bool $isPublished,
        public bool $isAvailable,
        public bool $isNew,
        public bool $isHot,
        public int $sort,
        public bool $isDeleted,
        public DateTimeImmutable $updatedAt,
    ) {}
}
