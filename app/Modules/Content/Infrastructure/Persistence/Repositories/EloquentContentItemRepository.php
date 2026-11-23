<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Repositories;

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Infrastructure\Persistence\Models\Banner;
use App\Modules\Content\Infrastructure\Persistence\Models\ContentModel;
use App\Modules\Content\Infrastructure\Persistence\Models\GalleryImage;
use App\Modules\Content\Infrastructure\Persistence\Models\HeroSlide;
use App\Modules\Content\Infrastructure\Persistence\Models\TrustBadge;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * One repository for all content types: the type chooses the model, its fields are the columns.
 */
final class EloquentContentItemRepository implements ContentItemRepository
{
    public function all(ContentType $type): array
    {
        $models = $this->model($type)->newQuery()->orderBy('sort')->orderBy('id')->get();
        $items = [];

        foreach ($models as $model) {
            $items[] = $this->toItem($type, $model);
        }

        return $items;
    }

    public function find(ContentType $type, int $id): ?ContentItem
    {
        $model = $this->model($type)->newQuery()->find($id);

        return $model === null ? null : $this->toItem($type, $model);
    }

    public function save(ContentItem $item): int
    {
        $model = $item->id === null
            ? $this->model($item->type)
            : $this->model($item->type)->newQuery()->findOrFail($item->id);

        $model->fill($this->toAttributes($item));
        $model->save();

        return $model->id;
    }

    public function delete(ContentType $type, int $id): void
    {
        $this->model($type)->newQuery()->findOrFail($id)->delete();
    }

    public function restore(ContentType $type, int $id): void
    {
        $this->model($type)::onlyTrashed()->findOrFail($id)->restore();
    }

    public function nextSortOrder(ContentType $type): SortOrder
    {
        // max() skips rows in the Trash (SoftDeletes) and gives null for an empty table.
        $highest = $this->model($type)->newQuery()->max('sort');

        return $highest === null ? SortOrder::first() : SortOrder::fromInt((int) $highest)->next();
    }

    private function model(ContentType $type): ContentModel
    {
        return match ($type) {
            ContentType::HeroSlide => new HeroSlide,
            ContentType::Banner => new Banner,
            ContentType::TrustBadge => new TrustBadge,
            ContentType::GalleryImage => new GalleryImage,
        };
    }

    private function toItem(ContentType $type, ContentModel $model): ContentItem
    {
        $values = [];

        foreach ($type->fields() as $field) {
            $values[$field->name] = (string) ($model->getAttribute($field->name) ?? '');
        }

        return ContentItem::reconstitute(
            $model->id,
            $type,
            $values,
            $type->isScheduled()
                ? DateRange::between($this->moment($model, 'starts_at'), $this->moment($model, 'ends_at'))
                : DateRange::always(),
            $model->is_active,
            SortOrder::fromInt($model->sort),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(ContentItem $item): array
    {
        $attributes = ['sort' => $item->sort->value(), 'is_active' => $item->isActive];

        foreach ($item->type->fields() as $field) {
            $value = $item->value($field->name);
            $attributes[$field->name] = $value === '' ? null : $value;
        }

        if ($item->type->isScheduled()) {
            $attributes['starts_at'] = $item->schedule->start();
            $attributes['ends_at'] = $item->schedule->end();
        }

        return $attributes;
    }

    private function moment(ContentModel $model, string $column): ?DateTimeImmutable
    {
        $value = $model->getAttribute($column);

        return $value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value) : null;
    }
}
