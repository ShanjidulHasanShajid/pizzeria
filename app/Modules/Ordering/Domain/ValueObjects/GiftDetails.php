<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\ValueObjects;

use App\Modules\Ordering\Domain\Services\FieldChecker;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * For whom a gift box is. Used from Phase 15.
 */
final readonly class GiftDetails
{
    public const NAME_MAX_LENGTH = 255;

    public const MESSAGE_MAX_LENGTH = 300;

    private function __construct(
        public string $recipientName,
        public PhoneNumber $recipientPhone,
        public ?string $message,
    ) {}

    /**
     * From the checkout form: name and phone are required, the message may be empty.
     */
    public static function fromInput(string $recipientName, string $recipientPhone, string $message): self
    {
        $checker = new FieldChecker;

        $name = $checker->line('recipient_name', $recipientName, self::NAME_MAX_LENGTH);
        $phone = $checker->check('recipient_phone', fn (): PhoneNumber => PhoneNumber::fromString($recipientPhone));
        $message = $checker->optionalText('gift_message', $message, self::MESSAGE_MAX_LENGTH);

        $checker->throwIfAnyFailed();

        return new self($name, $phone, $message === '' ? null : $message);
    }

    /** Rebuilds saved details. Only the mapper and tests call this. */
    public static function reconstitute(string $recipientName, PhoneNumber $recipientPhone, ?string $message): self
    {
        return new self($recipientName, $recipientPhone, $message);
    }
}
