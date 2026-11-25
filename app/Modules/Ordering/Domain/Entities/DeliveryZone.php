<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Entities;

use App\Modules\Ordering\Domain\ValueObjects\DeliveryZoneDetails;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * An area the shop delivers to, served by one branch.
 */
final class DeliveryZone
{
    /** The fee a new zone starts with; the admin can change it per zone (owner-decided 2026-11-25). */
    private const STARTING_FEE_POISHA = 8_000;

    private function __construct(
        private ?int $id,
        private DeliveryZoneDetails $details,
        private bool $isActive,
        private SortOrder $sort,
    ) {}

    public static function startingFee(): Money
    {
        return Money::fromPoisha(self::STARTING_FEE_POISHA);
    }

    /** A new zone that is not saved yet, so it has no id. */
    public static function create(DeliveryZoneDetails $details, bool $isActive, SortOrder $sort): self
    {
        return new self(null, $details, $isActive, $sort);
    }

    /** Rebuilds a saved zone. Only the mapper and tests call this. */
    public static function reconstitute(int $id, DeliveryZoneDetails $details, bool $isActive, SortOrder $sort): self
    {
        return new self($id, $details, $isActive, $sort);
    }

    public function change(DeliveryZoneDetails $details, bool $isActive): void
    {
        $this->details = $details;
        $this->isActive = $isActive;
    }

    public function toggle(): void
    {
        $this->isActive = ! $this->isActive;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function details(): DeliveryZoneDetails
    {
        return $this->details;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }
}
