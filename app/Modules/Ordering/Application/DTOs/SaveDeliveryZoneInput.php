<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\DTOs;

/**
 * The delivery zone form. Amounts are the text the admin typed, in taka.
 */
final readonly class SaveDeliveryZoneInput
{
    /**
     * @param  ?int  $id  null for a new zone
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public int $locationId,
        public string $feeTaka,
        public string $minimumOrderTaka,
        public bool $isActive,
    ) {}
}
