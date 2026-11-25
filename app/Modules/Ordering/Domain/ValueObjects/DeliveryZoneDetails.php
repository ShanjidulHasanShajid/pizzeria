<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Ordering\Domain\Services\FieldChecker;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;

/**
 * What the admin enters about a delivery zone: its name, the branch that serves it,
 * what delivery costs there and how much an order must come to.
 */
final readonly class DeliveryZoneDetails
{
    public const NAME_MAX_LENGTH = 100;

    /** Stops typing mistakes (and numbers the database column cannot hold). */
    public const MAX_TAKA = 1_000_000;

    private function __construct(
        public string $name,
        public int $locationId,
        public Money $fee,
        public Money $minimumOrder,
    ) {}

    /**
     * From a form: everything is checked and all problems are reported together.
     *
     * @param  string  $feeTaka  "80" or "80.50"; "0" is free delivery
     * @param  string  $minimumOrderTaka  empty or "0" means no minimum
     */
    public static function fromInput(string $name, int $locationId, string $feeTaka, string $minimumOrderTaka): self
    {
        $checker = new FieldChecker;

        $name = $checker->line('name', $name, self::NAME_MAX_LENGTH);
        $fee = $checker->check('fee', fn (): Money => self::amount($feeTaka));
        $minimumOrder = $checker->check('min_order', fn (): Money => trim($minimumOrderTaka) === '' ? Money::zero() : self::amount($minimumOrderTaka));

        if ($locationId < 1) {
            $checker->fail('location_id', 'Choose the branch that serves this zone.');
        }

        $checker->throwIfAnyFailed();

        return new self($name, $locationId, $fee, $minimumOrder);
    }

    /** Rebuilds saved details. Only the mapper and tests call this. */
    public static function reconstitute(string $name, int $locationId, Money $fee, Money $minimumOrder): self
    {
        return new self($name, $locationId, $fee, $minimumOrder);
    }

    private static function amount(string $taka): Money
    {
        $amount = Money::fromTaka($taka);

        if ($amount->isGreaterThan(Money::fromTaka(self::MAX_TAKA))) {
            throw InvalidValue::because('Enter at most 1000000 taka.');
        }

        return $amount;
    }
}
