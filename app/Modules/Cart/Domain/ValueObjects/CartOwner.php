<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * Whose cart it is: a logged-in customer (user id) or a guest (a secret token kept in the session).
 * Always exactly one of the two.
 */
final readonly class CartOwner
{
    public const TOKEN_LENGTH = 40;

    private function __construct(
        private ?int $userId,
        private ?string $guestToken,
    ) {}

    public static function customer(int $userId): self
    {
        if ($userId < 1) {
            throw InvalidValue::because('A cart needs a valid customer.');
        }

        return new self($userId, null);
    }

    public static function guest(string $token): self
    {
        if (preg_match('/^[A-Za-z0-9]{'.self::TOKEN_LENGTH.'}$/D', $token) !== 1) {
            throw InvalidValue::because('A guest cart needs a valid token.');
        }

        return new self(null, $token);
    }

    public function isGuest(): bool
    {
        return $this->guestToken !== null;
    }

    /** Null for a guest. */
    public function userId(): ?int
    {
        return $this->userId;
    }

    /** Null for a customer. */
    public function guestToken(): ?string
    {
        return $this->guestToken;
    }

    public function equals(self $other): bool
    {
        return $this->userId === $other->userId && $this->guestToken === $other->guestToken;
    }
}
