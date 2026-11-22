<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\DTOs\CreateSizeTierInput;
use App\Modules\Catalog\Application\DTOs\ReorderInput;
use App\Modules\Catalog\Application\DTOs\UpdateSizeTierInput;
use App\Modules\Catalog\Application\UseCases\SizeTiers\CreateSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\DeleteSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\ForceDeleteSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\ReorderSizeTiers;
use App\Modules\Catalog\Application\UseCases\SizeTiers\RestoreSizeTier;
use App\Modules\Catalog\Application\UseCases\SizeTiers\ToggleSizeTierActive;
use App\Modules\Catalog\Application\UseCases\SizeTiers\UpdateSizeTier;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use Tests\Unit\Catalog\Fakes\InMemorySizeTierRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

beforeEach(function (): void {
    $this->sizeTiers = new InMemorySizeTierRepository;
    $this->transactions = new PassThroughTransactionManager;
    $this->createSize = fn (string $name, ?string $slug = null): int => (new CreateSizeTier($this->sizeTiers, new SlugGenerator))
        ->handle(new CreateSizeTierInput($name, $slug));
});

describe('creating', function (): void {
    it('makes the slug from the name and puts the size at the end', function (): void {
        ($this->createSize)('Personal');
        $id = ($this->createSize)('Extra Large');

        expect($this->sizeTiers->find($id)->slug()->value())->toBe('extra-large')
            ->and($this->sizeTiers->find($id)->sort()->value())->toBe(20);
    });

    it('adds a number when the slug is taken, also by a size in the trash', function (): void {
        $first = ($this->createSize)('Medium');
        (new DeleteSizeTier($this->sizeTiers, $this->transactions))->handle(new CatalogItemInput($first));

        $second = ($this->createSize)('Medium');

        expect($this->sizeTiers->find($second)->slug()->value())->toBe('medium-2');
    });

    it('uses a typed slug, but refuses one that is taken', function (): void {
        $id = ($this->createSize)('Personal', 'small');

        expect($this->sizeTiers->find($id)->slug()->value())->toBe('small');
        expect(fn () => ($this->createSize)('Small', 'small'))->toThrow(ValidationFailed::class);
    });
});

describe('updating', function (): void {
    it('keeps the slug when the slug field is empty', function (): void {
        $id = ($this->createSize)('Medium');

        (new UpdateSizeTier($this->sizeTiers))->handle(new UpdateSizeTierInput($id, 'Regular', slug: '', isActive: true));

        expect($this->sizeTiers->find($id)->name())->toBe('Regular')
            ->and($this->sizeTiers->find($id)->slug()->value())->toBe('medium');
    });

    it('changes the slug, may keep its own, and refuses one of another size', function (): void {
        ($this->createSize)('Personal');
        $id = ($this->createSize)('Medium');
        $update = new UpdateSizeTier($this->sizeTiers);

        $update->handle(new UpdateSizeTierInput($id, 'Medium', slug: 'medium', isActive: true));
        $update->handle(new UpdateSizeTierInput($id, 'Medium', slug: 'regular', isActive: true));
        expect($this->sizeTiers->find($id)->slug()->value())->toBe('regular');

        try {
            $update->handle(new UpdateSizeTierInput($id, 'Medium', slug: 'personal', isActive: true));
            $this->fail('A taken slug was accepted.');
        } catch (ValidationFailed $failure) {
            expect($failure->errors())->toHaveKey('slug');
        }
    });

    it('switches a size off and on again', function (): void {
        $id = ($this->createSize)('Large');
        $toggle = new ToggleSizeTierActive($this->sizeTiers);

        expect($toggle->handle(new CatalogItemInput($id)))->toBeFalse()
            ->and($this->sizeTiers->find($id)->isActive())->toBeFalse()
            ->and($toggle->handle(new CatalogItemInput($id)))->toBeTrue();
    });
});

describe('deleting and order', function (): void {
    it('refuses to delete a size that variants use', function (): void {
        $id = ($this->createSize)('Personal');
        $this->sizeTiers->markInUse($id);

        expect(fn () => (new DeleteSizeTier($this->sizeTiers, $this->transactions))->handle(new CatalogItemInput($id)))
            ->toThrow(CatalogItemInUse::class, 'used by product variants');
        expect($this->sizeTiers->isDeleted($id))->toBeFalse();
    });

    it('restores a size from the trash', function (): void {
        $id = ($this->createSize)('Personal');
        (new DeleteSizeTier($this->sizeTiers, $this->transactions))->handle(new CatalogItemInput($id));

        (new RestoreSizeTier($this->sizeTiers))->handle(new CatalogItemInput($id));

        expect($this->sizeTiers->find($id))->not->toBeNull();
    });

    it('saves a new order', function (): void {
        $personal = ($this->createSize)('Personal');
        $medium = ($this->createSize)('Medium');

        (new ReorderSizeTiers($this->sizeTiers, $this->transactions))->handle(new ReorderInput([$medium, $personal]));

        expect($this->sizeTiers->find($medium)->sort()->value())->toBe(10)
            ->and($this->sizeTiers->find($personal)->sort()->value())->toBe(20);
    });
});

describe('deleting forever', function (): void {
    it('deletes a size in the trash forever', function (): void {
        $id = ($this->createSize)('Mini');
        $this->sizeTiers->delete($id);

        (new ForceDeleteSizeTier($this->sizeTiers))->handle(new CatalogItemInput($id));

        expect($this->sizeTiers->exists($id))->toBeFalse();
    });

    it('deletes a size forever only from the trash, and not while old variants still use it', function (): void {
        $forceDelete = new ForceDeleteSizeTier($this->sizeTiers);
        $live = ($this->createSize)('Personal');
        $trashed = ($this->createSize)('Mini');
        $this->sizeTiers->markUsedInTrash($trashed);
        $this->sizeTiers->delete($trashed);

        expect(fn () => $forceDelete->handle(new CatalogItemInput($live)))->toThrow(EntityNotFound::class);
        expect(fn () => $forceDelete->handle(new CatalogItemInput($trashed)))->toThrow(CatalogItemInUse::class, 'deleted forever');
        expect($this->sizeTiers->exists($trashed))->toBeTrue();
    });
});
