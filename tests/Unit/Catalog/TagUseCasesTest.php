<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateTagInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateTagInput;
use App\Modules\Catalog\Application\UseCases\Tags\CreateTag;
use App\Modules\Catalog\Application\UseCases\Tags\DeleteTag;
use App\Modules\Catalog\Application\UseCases\Tags\ForceDeleteTag;
use App\Modules\Catalog\Application\UseCases\Tags\ReorderTags;
use App\Modules\Catalog\Application\UseCases\Tags\RestoreTag;
use App\Modules\Catalog\Application\UseCases\Tags\UpdateTag;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use Tests\Unit\Catalog\Fakes\InMemoryTagRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->tags = new InMemoryTagRepository;
    $this->createTag = fn (string $name, ?string $slug = null, TagType $type = TagType::Dietary, ?string $icon = null): int => (new CreateTag($this->tags, new SlugGenerator))
        ->handle(new CreateTagInput($name, $slug, $type, $icon));
});

it('creates a tag with type, icon and a slug made from the name', function (): void {
    $id = ($this->createTag)('Contains Nuts', type: TagType::Allergen, icon: 'exclamation-triangle');

    $tag = $this->tags->find($id);

    expect($tag->slug()->value())->toBe('contains-nuts')
        ->and($tag->type())->toBe(TagType::Allergen)
        ->and($tag->icon())->toBe('exclamation-triangle')
        ->and($tag->sort()->value())->toBe(10)
        ->and($tag->isActive())->toBeTrue();
});

it('refuses a typed slug that another tag has', function (): void {
    ($this->createTag)('Veg');

    ($this->createTag)('Vegetarian', slug: 'veg');
})->throws(ValidationFailed::class);

it('changes name, type and icon, and keeps the slug when the field is empty', function (): void {
    $id = ($this->createTag)('Hot', type: TagType::Flavour, icon: 'fire');

    (new UpdateTag($this->tags))->handle(new UpdateTagInput(
        id: $id, name: 'Spicy', slug: null, type: TagType::Flavour, icon: null, isActive: false,
    ));

    $tag = $this->tags->find($id);

    expect($tag->name())->toBe('Spicy')
        ->and($tag->slug()->value())->toBe('hot')
        ->and($tag->icon())->toBeNull()
        ->and($tag->isActive())->toBeFalse();
});

it('always lets a tag go to the trash, and brings it back', function (): void {
    $id = ($this->createTag)('Seafood');

    (new DeleteTag($this->tags))->handle(new CatalogItemInput($id));
    expect($this->tags->isDeleted($id))->toBeTrue();

    (new RestoreTag($this->tags))->handle(new CatalogItemInput($id));
    expect($this->tags->find($id))->not->toBeNull();
});

it('reports an unknown tag when deleting', function (): void {
    (new DeleteTag($this->tags))->handle(new CatalogItemInput(99));
})->throws(EntityNotFound::class);

it('saves a new order', function (): void {
    $veg = ($this->createTag)('Veg');
    $spicy = ($this->createTag)('Spicy');

    (new ReorderTags($this->tags, new PassThroughTransactionManager))->handle(new ReorderInput([$spicy, $veg]));

    expect($this->tags->find($spicy)->sort()->value())->toBe(10)
        ->and($this->tags->find($veg)->sort()->value())->toBe(20);
});

it('deletes a tag forever, but only from the trash', function (): void {
    $id = ($this->createTag)('Mild');
    $forceDelete = new ForceDeleteTag($this->tags);

    expect(fn () => $forceDelete->handle(new CatalogItemInput($id)))->toThrow(EntityNotFound::class);

    $this->tags->delete($id);
    $forceDelete->handle(new CatalogItemInput($id));

    expect($this->tags->exists($id))->toBeFalse();
});
