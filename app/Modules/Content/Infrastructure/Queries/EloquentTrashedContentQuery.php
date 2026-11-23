<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Queries;

use App\Modules\Content\Application\DTOs\TrashedContentItem;
use App\Modules\Content\Application\Queries\TrashedContentQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Infrastructure\Persistence\Models\ContentModel;
use App\Modules\Content\Infrastructure\Persistence\Models\ContentModels;
use App\Modules\Content\Infrastructure\Persistence\Models\Location;
use DateTimeImmutable;

final class EloquentTrashedContentQuery implements TrashedContentQuery
{
    public function items(): array
    {
        $items = [];

        foreach (ContentType::cases() as $type) {
            foreach (ContentModels::for($type)::onlyTrashed()->get() as $model) {
                $items[] = new TrashedContentItem(
                    $type,
                    $model->id,
                    $this->name($type, $model),
                    $model->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
                );
            }
        }

        foreach (Location::onlyTrashed()->get() as $location) {
            $items[] = new TrashedContentItem(
                null,
                $location->id,
                $location->name,
                $location->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
            );
        }

        return $items;
    }

    /** The title or the caption; a photo without a caption is "Gallery image 7". */
    private function name(ContentType $type, ContentModel $model): string
    {
        $name = (string) ($model->getAttribute('title') ?? $model->getAttribute('caption') ?? '');

        return $name === '' ? "{$type->singular()} {$model->id}" : $name;
    }
}
