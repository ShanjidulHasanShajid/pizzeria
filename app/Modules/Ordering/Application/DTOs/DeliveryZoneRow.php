<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * One delivery zone on the admin list and edit page.
 */
final readonly class DeliveryZoneRow
{
    /**
     * @param  ?Branch  $branch  null when the branch is deleted
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $locationId,
        public ?Branch $branch,
        public Money $fee,
        public Money $minimumOrder,
        public bool $isActive,
    ) {}
}
