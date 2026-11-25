<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * A wish for the kitchen on one line ("less spicy, please"). It may be empty.
 */
final readonly class KitchenNote
{
    public const MAX_LENGTH = 200;

    private function __construct(private string $text) {}

    public static function none(): self
    {
        return new self('');
    }

    public static function of(?string $text): self
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text ?? ''));

        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw InvalidValue::because(sprintf('A note can have at most %d characters.', self::MAX_LENGTH));
        }

        return new self($text);
    }

    public function isEmpty(): bool
    {
        return $this->text === '';
    }

    /** Null when there is no note. */
    public function text(): ?string
    {
        return $this->isEmpty() ? null : $this->text;
    }

    public function equals(self $other): bool
    {
        return $this->text === $other->text;
    }
}
