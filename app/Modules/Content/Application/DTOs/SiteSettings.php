<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Shared\Domain\ValueObjects\Percentage;

/**
 * All settings with their real types, for the storefront and for other modules.
 * An empty text means "show nothing"; an image path is null when there is no image.
 */
final readonly class SiteSettings
{
    public function __construct(
        public string $siteName,
        public ?string $logoPath,
        public ?string $faviconPath,
        public string $phone,
        public string $email,
        public string $addressLine,
        public string $facebookUrl,
        public string $instagramUrl,
        public string $youtubeUrl,
        public Percentage $vatPercentage,
        public bool $pricesIncludeVat,
        public bool $isStoreOpen,
        public int $hotItemsLimit,
        public string $builderTitle,
        public string $builderText,
        public string $combosStripTitle,
        public string $openingHoursText,
        public string $deliveryNotice,
    ) {}
}
