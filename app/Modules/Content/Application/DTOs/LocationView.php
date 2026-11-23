<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\ValueObjects\Location;
use DateTimeImmutable;

/**
 * One branch for the shop and the admin screens.
 */
final readonly class LocationView
{
    /**
     * @param  array<string, array{string, string}|null>  $hours  all seven weekdays; null is closed
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $address,
        public string $area,
        public string $phone,
        public string $mapEmbedUrl,
        public string $latitude,
        public string $longitude,
        public array $hours,
        public bool $isPickupAvailable,
        public bool $isActive,
        public bool $isOpenNow,
    ) {}

    public static function fromLocation(Location $location, DateTimeImmutable $now): self
    {
        $details = $location->details;

        return new self(
            (int) $location->id,
            $details->name,
            $details->address,
            $details->area,
            $details->phone,
            $details->mapEmbedUrl,
            $details->coordinates === null ? '' : $details->coordinates->latitude,
            $details->coordinates === null ? '' : $details->coordinates->longitude,
            $location->hours->toWeek(),
            $details->isPickupAvailable,
            $location->isActive,
            $location->isActive && $location->hours->isOpenAt($now),
        );
    }
}
