<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * The answer for one of several lines: either the priced line or the problem, never both.
 */
final readonly class QuotedLine
{
    /**
     * @param  ?string  $problem  for the customer
     */
    private function __construct(
        public ?PricedLineView $line,
        public ?string $problem,
    ) {}

    public static function priced(PricedLineView $line): self
    {
        return new self($line, null);
    }

    public static function refused(string $problem): self
    {
        return new self(null, $problem);
    }
}
