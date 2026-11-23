<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Content\Application\DTOs\TrashedContentItem;
use App\Modules\Content\Application\Queries\TrashedContentQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Shared\Presentation\Support\TrashItem;
use App\Modules\Shared\Presentation\Support\TrashKind;
use App\Modules\Shared\Presentation\Support\TrashSource;

/**
 * What the Content module adds to the Trash: one kind per content type, and the locations.
 */
final class ContentTrash implements TrashSource
{
    private const LOCATION = 'location';

    public function __construct(private readonly TrashedContentQuery $content) {}

    public function kinds(): array
    {
        $kinds = array_map(fn (ContentType $type): TrashKind => new TrashKind(
            $type->value,
            $type->label(),
            $type->singular(),
            "admin.{$type->value}",
            $type === ContentType::TrustBadge ? 'The trust badge is deleted for good.' : "The {$this->lower($type)} and its image files are deleted for good.",
            'manage-content',
        ), ContentType::cases());

        $kinds[] = new TrashKind(self::LOCATION, 'Locations', 'Location', 'admin.locations', 'The location is deleted for good. Old orders keep their own copy of its name.', 'manage-content');

        return $kinds;
    }

    public function items(): array
    {
        return array_map(
            fn (TrashedContentItem $item): TrashItem => new TrashItem($item->type === null ? self::LOCATION : $item->type->value, $item->id, $item->name, $item->deletedAt),
            $this->content->items(),
        );
    }

    private function lower(ContentType $type): string
    {
        return strtolower($type->singular());
    }
}
