<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Requests\Concerns;

use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;

/**
 * Cleans the email and phone fields BEFORE validation, so "unique" compares
 * 01712345678 with 01712345678 even if the visitor typed +880 1712-345678.
 */
trait NormalizesContactFields
{
    protected function normalizeContactFields(): void
    {
        $clean = [];

        $email = $this->input('email');
        if (is_string($email)) {
            $clean['email'] = mb_strtolower(trim($email));
        }

        $phone = $this->input('phone');
        if (is_string($phone) && ($number = PhoneNumber::tryFrom($phone)) !== null) {
            $clean['phone'] = $number->value();
        }

        $this->merge($clean);
    }
}
