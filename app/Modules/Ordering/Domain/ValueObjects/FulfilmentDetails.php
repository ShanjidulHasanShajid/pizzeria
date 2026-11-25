<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Ordering\Domain\Enums\OrderType;
use App\Modules\Ordering\Domain\Services\FieldChecker;
use DateTimeImmutable;

/**
 * How and when the customer gets the order. The names of the branch and the zone are copies:
 * old orders stay readable when the branch or the zone is renamed or deleted.
 * Whether the zone is active or the time is possible is checked by the checkout policies.
 */
final readonly class FulfilmentDetails
{
    public const ADDRESS_MAX_LENGTH = 500;

    public const NOTES_MAX_LENGTH = 500;

    /**
     * @param  ?int  $locationId  null only in an old order whose branch was deleted for good
     * @param  ?int  $zoneId  null for pickup, and in an old order whose zone was deleted for good
     * @param  ?string  $zoneName  null for pickup
     * @param  ?string  $address  null for pickup
     * @param  ?DateTimeImmutable  $scheduledAt  null means as soon as possible
     */
    private function __construct(
        public OrderType $type,
        public ?int $locationId,
        public string $locationName,
        public ?int $zoneId,
        public ?string $zoneName,
        public ?string $address,
        public ?DateTimeImmutable $scheduledAt,
        public ?string $notes,
    ) {}

    public static function delivery(
        int $locationId,
        string $locationName,
        int $zoneId,
        string $zoneName,
        string $address,
        ?DateTimeImmutable $scheduledAt,
        string $notes,
    ): self {
        $checker = new FieldChecker;

        $address = $checker->text('delivery_address', $address, self::ADDRESS_MAX_LENGTH);
        $notes = $checker->optionalText('notes', $notes, self::NOTES_MAX_LENGTH);

        $checker->throwIfAnyFailed();

        return new self(OrderType::Delivery, $locationId, $locationName, $zoneId, $zoneName, $address, $scheduledAt, $notes === '' ? null : $notes);
    }

    public static function pickup(int $locationId, string $locationName, ?DateTimeImmutable $scheduledAt, string $notes): self
    {
        $checker = new FieldChecker;

        $notes = $checker->optionalText('notes', $notes, self::NOTES_MAX_LENGTH);

        $checker->throwIfAnyFailed();

        return new self(OrderType::Pickup, $locationId, $locationName, null, null, null, $scheduledAt, $notes === '' ? null : $notes);
    }

    /** Rebuilds saved details. Only the mapper and tests call this. */
    public static function reconstitute(
        OrderType $type,
        ?int $locationId,
        string $locationName,
        ?int $zoneId,
        ?string $zoneName,
        ?string $address,
        ?DateTimeImmutable $scheduledAt,
        ?string $notes,
    ): self {
        return new self($type, $locationId, $locationName, $zoneId, $zoneName, $address, $scheduledAt, $notes);
    }

    public function isAsSoonAsPossible(): bool
    {
        return $this->scheduledAt === null;
    }
}
