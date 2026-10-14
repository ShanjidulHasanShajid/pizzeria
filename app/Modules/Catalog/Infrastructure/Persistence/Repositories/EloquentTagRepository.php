<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repositories;

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

final class EloquentTagRepository implements TagRepository
{
    public function find(int $id): ?Tag
    {
        $model = TagModel::query()->find($id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function save(Tag $tag): int
    {
        $id = $tag->id();
        $model = $id === null ? new TagModel : TagModel::query()->findOrFail($id);

        $model->fill([
            'name' => $tag->name(),
            'slug' => $tag->slug()->value(),
            'type' => $tag->type(),
            'icon' => $tag->icon(),
            'sort' => $tag->sort()->value(),
            'is_active' => $tag->isActive(),
        ]);
        $model->save();

        return $model->id;
    }

    public function delete(int $id): void
    {
        TagModel::query()->findOrFail($id)->delete();
    }

    public function restore(int $id): void
    {
        TagModel::onlyTrashed()->findOrFail($id)->restore();
    }

    public function slugExists(Slug $slug, ?int $ignoreId = null): bool
    {
        $query = TagModel::withTrashed()->where('slug', $slug->value());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function toEntity(TagModel $model): Tag
    {
        return Tag::reconstitute(
            $model->id,
            $model->name,
            Slug::fromString($model->slug),
            $model->type,
            $model->icon,
            SortOrder::fromInt($model->sort),
            $model->is_active,
        );
    }
}
