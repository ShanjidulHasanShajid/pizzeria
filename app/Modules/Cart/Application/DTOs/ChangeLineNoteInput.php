<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\DTOs;

final readonly class ChangeLineNoteInput
{
    public function __construct(
        public int $lineId,
        public ?string $note,
    ) {}
}
