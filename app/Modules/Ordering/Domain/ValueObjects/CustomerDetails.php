<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Ordering\Domain\Services\FieldChecker;
use App\Modules\Shared\Domain\ValueObjects\EmailAddress;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * Who ordered. Kept on the order itself, so a guest needs no account and a later change
 * of an account does not change old orders.
 */
final readonly class CustomerDetails
{
    public const NAME_MAX_LENGTH = 255;

    private function __construct(
        public string $name,
        public PhoneNumber $phone,
        public ?EmailAddress $email,
    ) {}

    /**
     * From the checkout form: name and phone are required, an empty email is fine.
     */
    public static function fromInput(string $name, string $phone, string $email): self
    {
        $checker = new FieldChecker;

        $name = $checker->line('name', $name, self::NAME_MAX_LENGTH);
        $phone = $checker->check('phone', fn (): PhoneNumber => PhoneNumber::fromString($phone));
        $email = $checker->check('email', fn (): ?EmailAddress => trim($email) === '' ? null : EmailAddress::fromString($email));

        $checker->throwIfAnyFailed();

        return new self($name, $phone, $email);
    }

    /** Rebuilds saved details. Only the mapper and tests call this. */
    public static function reconstitute(string $name, PhoneNumber $phone, ?EmailAddress $email): self
    {
        return new self($name, $phone, $email);
    }
}
