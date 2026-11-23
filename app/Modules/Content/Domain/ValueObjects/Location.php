<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A branch of the shop. Plain data: a change makes a new object.
 */
final readonly class Location
{
    private function __construct(
        public ?int $id,
        public LocationDetails $details,
        public OpeningHours $hours,
        public bool $isActive,
        public SortOrder $sort,
    ) {}

    public static function create(LocationDetails $details, OpeningHours $hours, bool $isActive, SortOrder $sort): self
    {
        return new self(null, $details, $hours, $isActive, $sort);
    }

    /** Rebuilds a saved location. Only the repository and tests call this. */
    public static function reconstitute(int $id, LocationDetails $details, OpeningHours $hours, bool $isActive, SortOrder $sort): self
    {
        return new self($id, $details, $hours, $isActive, $sort);
    }

    public function change(LocationDetails $details, OpeningHours $hours, bool $isActive): self
    {
        return new self($this->id, $details, $hours, $isActive, $this->sort);
    }

    public function toggled(): self
    {
        return new self($this->id, $this->details, $this->hours, ! $this->isActive, $this->sort);
    }

    public function movedTo(SortOrder $sort): self
    {
        return new self($this->id, $this->details, $this->hours, $this->isActive, $sort);
    }
}
