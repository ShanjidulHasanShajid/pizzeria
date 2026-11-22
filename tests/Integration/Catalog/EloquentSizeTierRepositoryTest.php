<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->repository = app(SizeTierRepository::class);
});

it('saves a new size tier and finds it again', function (): void {
    $id = $this->repository->save(SizeTier::create('Medium', Slug::fromString('medium'), SortOrder::fromInt(20)));

    $medium = $this->repository->find($id);

    expect($medium->name())->toBe('Medium')
        ->and($medium->slug()->value())->toBe('medium')
        ->and($medium->sort()->value())->toBe(20)
        ->and($medium->isActive())->toBeTrue();
});

it('updates a saved size tier instead of adding a second row', function (): void {
    $id = $this->repository->save(SizeTier::create('Medium', Slug::fromString('medium'), SortOrder::fromInt(20)));
    $medium = $this->repository->find($id);

    $medium->rename('Regular');
    $medium->changeSlug(Slug::fromString('regular'));
    $medium->deactivate();
    $this->repository->save($medium);

    $regular = $this->repository->find($id);

    expect(SizeTierModel::query()->count())->toBe(1)
        ->and($regular->name())->toBe('Regular')
        ->and($regular->slug()->value())->toBe('regular')
        ->and($regular->isActive())->toBeFalse();
});

it('moves a size tier to the Trash and back', function (): void {
    $id = SizeTierModel::factory()->create()->id;

    $this->repository->delete($id);

    expect($this->repository->find($id))->toBeNull();
    $this->assertSoftDeleted('size_tiers', ['id' => $id]);

    $this->repository->restore($id);

    expect($this->repository->find($id))->not->toBeNull();
});

it('finds a taken slug, also in the Trash, but not the slug of the size being edited', function (): void {
    $id = $this->repository->save(SizeTier::create('Medium', Slug::fromString('medium'), SortOrder::fromInt(20)));

    expect($this->repository->slugExists(Slug::fromString('medium')))->toBeTrue()
        ->and($this->repository->slugExists(Slug::fromString('family')))->toBeFalse()
        ->and($this->repository->slugExists(Slug::fromString('medium'), ignoreId: $id))->toBeFalse();

    $this->repository->delete($id);

    expect($this->repository->slugExists(Slug::fromString('medium')))->toBeTrue();
});

it('knows when a variant that is not deleted uses a size tier', function (): void {
    $medium = SizeTierModel::factory()->create();
    $family = SizeTierModel::factory()->create();
    $variant = ProductVariantModel::factory()->forSizeTier($medium)->create();

    expect($this->repository->isInUse($medium->id))->toBeTrue()
        ->and($this->repository->isInUse($family->id))->toBeFalse();

    $variant->delete();

    expect($this->repository->isInUse($medium->id))->toBeFalse();
});

it('gives the position after the last size tier that is not deleted', function (): void {
    expect($this->repository->nextSortOrder()->value())->toBe(10);

    SizeTierModel::factory()->create(['sort' => 20]);
    SizeTierModel::factory()->create(['sort' => 70])->delete();

    expect($this->repository->nextSortOrder()->value())->toBe(30);
});

it('finds a size in the Trash, sees removed variants, and deletes it for good', function (): void {
    $mini = SizeTierModel::factory()->create(['name' => 'Mini']);
    $variant = ProductVariantModel::factory()->forSizeTier($mini)->create();
    $variant->delete();
    $mini->delete();

    expect($this->repository->findTrashed($mini->id)?->name())->toBe('Mini')
        ->and($this->repository->isInUse($mini->id))->toBeFalse()
        ->and($this->repository->isUsedIncludingTrash($mini->id))->toBeTrue();

    $variant->forceDelete();
    $this->repository->forceDelete($mini->id);

    $this->assertDatabaseMissing('size_tiers', ['id' => $mini->id]);
});
