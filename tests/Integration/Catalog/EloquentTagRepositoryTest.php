<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Tag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Domain\Repositories\TagRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->repository = app(TagRepository::class);
});

it('saves a new tag with its type and icon and finds it again', function (): void {
    $id = $this->repository->save(Tag::create('Spicy', Slug::fromString('spicy'), TagType::Flavour, SortOrder::fromInt(30), icon: 'fire'));

    $spicy = $this->repository->find($id);

    expect($spicy->name())->toBe('Spicy')
        ->and($spicy->slug()->value())->toBe('spicy')
        ->and($spicy->type())->toBe(TagType::Flavour)
        ->and($spicy->icon())->toBe('fire')
        ->and($spicy->sort()->value())->toBe(30)
        ->and($spicy->isActive())->toBeTrue();
});

it('updates a saved tag instead of adding a second row', function (): void {
    $id = $this->repository->save(Tag::create('Spicy', Slug::fromString('spicy'), TagType::Flavour, SortOrder::fromInt(30), icon: 'fire'));
    $tag = $this->repository->find($id);

    $tag->rename('Contains Nuts');
    $tag->changeType(TagType::Allergen);
    $tag->changeIcon(null);
    $tag->deactivate();
    $this->repository->save($tag);

    $nuts = $this->repository->find($id);

    expect(TagModel::query()->count())->toBe(1)
        ->and($nuts->name())->toBe('Contains Nuts')
        ->and($nuts->type())->toBe(TagType::Allergen)
        ->and($nuts->icon())->toBeNull()
        ->and($nuts->isActive())->toBeFalse();
});

it('moves a tag to the Trash and back', function (): void {
    $id = TagModel::factory()->create()->id;

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('tags', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id))->not->toBeNull();
});

it('finds a taken slug, also in the Trash, but not the slug of the tag being edited', function (): void {
    $id = $this->repository->save(Tag::create('Veg', Slug::fromString('veg'), TagType::Dietary, SortOrder::fromInt(10)));

    expect($this->repository->slugExists(Slug::fromString('veg')))->toBeTrue()
        ->and($this->repository->slugExists(Slug::fromString('vegan')))->toBeFalse()
        ->and($this->repository->slugExists(Slug::fromString('veg'), ignoreId: $id))->toBeFalse();

    $this->repository->delete($id);

    expect($this->repository->slugExists(Slug::fromString('veg')))->toBeTrue();
});
