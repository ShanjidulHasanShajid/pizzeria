<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\DTOs\ContentItemView;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Shared\Application\Ports\Clock;

/**
 * Reads through the (cached) repositories and applies the clock.
 */
final class RepositoryContentQuery implements ContentQuery
{
    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly HomeLayoutRepository $layouts,
        private readonly Clock $clock,
    ) {}

    public function visible(ContentType $type): array
    {
        $now = $this->clock->now();
        $visible = array_filter($this->items->all($type), fn (ContentItem $item): bool => $item->isVisibleAt($now));

        return array_values(array_map(fn (ContentItem $item): ContentItemView => ContentItemView::fromItem($item, $now), $visible));
    }

    public function table(ContentType $type): array
    {
        $now = $this->clock->now();

        return array_map(fn (ContentItem $item): ContentItemView => ContentItemView::fromItem($item, $now), $this->items->all($type));
    }

    public function item(ContentType $type, int $id): ?ContentItemView
    {
        $item = $this->items->find($type, $id);

        return $item === null ? null : ContentItemView::fromItem($item, $this->clock->now());
    }

    public function homeLayout(): HomeLayout
    {
        return $this->layouts->get();
    }
}
