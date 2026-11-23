<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

/**
 * The location form as it was typed.
 */
final readonly class SaveLocationInput
{
    /**
     * @param  ?int  $id  null for a new location
     * @param  array<string, array{string, string}|null>  $hours  weekday => opening and closing time; null is closed
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $address,
        public string $area,
        public string $phone,
        public string $mapEmbed,
        public string $latitude,
        public string $longitude,
        public array $hours,
        public bool $isPickupAvailable,
        public bool $isActive,
    ) {}
}
