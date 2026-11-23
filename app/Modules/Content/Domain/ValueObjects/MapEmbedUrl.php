<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * The address of a Google map that may be shown inside our page (Google Maps: Share, Embed a map).
 * Nothing else is accepted: what stands in an iframe becomes part of the page.
 */
final readonly class MapEmbedUrl
{
    public const MAX_LENGTH = 1000;

    private function __construct(private string $value) {}

    /**
     * Accepts the address itself or the whole <iframe ...> code that Google offers.
     */
    public static function fromInput(string $input): self
    {
        $url = trim($input);

        if (preg_match('/^<iframe\b[^>]*\ssrc="([^"]+)"/i', $url, $matches) === 1) {
            $url = html_entity_decode($matches[1]);
        }

        if (! self::isGenuine($url)) {
            throw InvalidValue::because('Paste the map code from Google Maps: Share, then "Embed a map", then "Copy HTML".');
        }

        return new self($url);
    }

    public function value(): string
    {
        return $this->value;
    }

    private static function isGenuine(string $url): bool
    {
        if (strlen($url) > self::MAX_LENGTH || preg_match('#^https://www\.google\.com/maps/embed\?[^\s"\'<>\\\\\#]+$#D', $url) !== 1) {
            return false;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return is_string($query['pb'] ?? null) && $query['pb'] !== '';
    }
}
