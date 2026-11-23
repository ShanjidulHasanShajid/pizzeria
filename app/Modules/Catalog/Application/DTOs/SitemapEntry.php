<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

use DateTimeImmutable;

/**
 * A category or product page that search engines should know.
 */
final readonly class SitemapEntry
{
    /**
     * @param  'category'|'product'  $kind
     * @param  ?string  $parentSlug  only for a sub-category
     */
    public function __construct(
        public string $kind,
        public string $slug,
        public ?string $parentSlug,
        public ?DateTimeImmutable $changedAt,
    ) {}
}
