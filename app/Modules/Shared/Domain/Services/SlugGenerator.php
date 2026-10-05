<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Services;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Turns a title into a unique slug: "Margherita Pizza" -> margherita-pizza,
 * and margherita-pizza-2, margherita-pizza-3 ... when the slug is taken.
 *
 * Only plain English letters and digits are kept. Text with no such characters
 * (for example Bengali only) becomes "item"; the admin can edit the slug.
 */
final class SlugGenerator
{
    /** Room kept free at the end of the slug for "-2", "-3" ... */
    private const SUFFIX_RESERVE = 6;

    private const MAX_ATTEMPTS = 1000;

    /**
     * @param  callable(Slug): bool  $exists  Must return true when the slug is already used.
     *                                        It must also look at soft-deleted rows.
     */
    public function generate(string $text, callable $exists): Slug
    {
        $base = $this->normalize($text);
        $candidate = Slug::fromString($base);

        for ($number = 2; $exists($candidate); $number++) {
            if ($number > self::MAX_ATTEMPTS) {
                throw InvalidValue::because('Could not find a free slug. Please type one by hand.');
            }

            $candidate = Slug::fromString($base.'-'.$number);
        }

        return $candidate;
    }

    private function normalize(string $text): string
    {
        $text = str_replace('&', ' and ', mb_strtolower($text));
        $text = trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
        $text = rtrim(substr($text, 0, Slug::MAX_LENGTH - self::SUFFIX_RESERVE), '-');

        return $text === '' ? 'item' : $text;
    }
}
