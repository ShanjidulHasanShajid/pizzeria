<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * The rules of an attached group as the admin typed them.
 */
final readonly class SelectionRulesInput
{
    public function __construct(
        public bool $isRequired,
        public int $minimum,
        public int $maximum,
        public int $freeCount,
    ) {}

    /**
     * Numbers that cannot work become a form error under "rules".
     */
    public function toRules(): SelectionRules
    {
        try {
            return SelectionRules::of($this->isRequired, $this->minimum, $this->maximum, $this->freeCount);
        } catch (InvalidValue $invalid) {
            throw ValidationFailed::forField('rules', $invalid->getMessage());
        }
    }
}
