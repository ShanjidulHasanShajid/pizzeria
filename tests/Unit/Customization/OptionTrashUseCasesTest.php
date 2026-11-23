<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\OptionGroupItemInput;
use App\Modules\Customization\Application\DTOs\TrashedOptionInput;
use App\Modules\Customization\Application\Services\OptionImages;
use App\Modules\Customization\Application\UseCases\OptionGroups\ForceDeleteOptionGroup;
use App\Modules\Customization\Application\UseCases\Options\ForceDeleteOption;
use App\Modules\Customization\Application\UseCases\Options\RestoreOption;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Exceptions\OptionGroupInUse;
use App\Modules\Customization\Domain\Exceptions\OptionRestoreBlocked;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Customization\Fakes\InMemoryProductOptionGroupRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/*
| Group 1 "Crust": Thin (option 1, with an image) and Stuffed (option 2).
*/
beforeEach(function (): void {
    $this->files = new InMemoryFileStorage;
    $this->images = new OptionImages($this->files, new RecordingImageProcessor);
    $this->groups = new InMemoryOptionGroupRepository;
    $this->attachments = new InMemoryProductOptionGroupRepository;

    $thin = Option::withFlatPrice('Thin', Money::zero(), SortOrder::first());
    $thin->changeImage($this->files->store('options', 'picture', 'webp'));
    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption($thin);
    $crust->addOption(Option::withPricesPerSize('Stuffed', [2 => Money::fromTaka('150')], SortOrder::fromInt(20)));
    $this->groups->save($crust);

    $this->removeOption = function (int $optionId): void {
        $crust = $this->groups->find(1);
        $crust->removeOption($optionId);
        $this->groups->save($crust);
    };
    $this->restoreOption = fn (int $optionId) => (new RestoreOption($this->groups, new PassThroughTransactionManager))
        ->handle(new TrashedOptionInput($optionId));
    $this->forceDeleteOption = fn (int $optionId) => (new ForceDeleteOption($this->groups, $this->images))
        ->handle(new TrashedOptionInput($optionId));
    $this->forceDeleteGroup = fn (int $id) => (new ForceDeleteOptionGroup($this->groups, $this->attachments, $this->images))
        ->handle(new OptionGroupItemInput($id));
});

it('restores an option into its group, with its prices', function (): void {
    ($this->removeOption)(2);

    ($this->restoreOption)(2);

    expect($this->groups->find(1)->option(2)->priceFor(2)->poisha())->toBe(15_000);
});

it('does not restore an option while its group is in the trash', function (): void {
    ($this->removeOption)(2);
    $this->groups->delete(1);

    expect(fn () => ($this->restoreOption)(2))->toThrow(OptionRestoreBlocked::class, 'Restore the group first');
});

it('does not restore an option when its name is taken by now', function (): void {
    ($this->removeOption)(2);
    $crust = $this->groups->find(1);
    $crust->addOption(Option::withFlatPrice('STUFFED', Money::zero(), SortOrder::fromInt(30)));
    $this->groups->save($crust);

    expect(fn () => ($this->restoreOption)(2))->toThrow(OptionRestoreBlocked::class, 'another option with this name');
});

it('deletes an option forever, with its image file, but only from the trash', function (): void {
    expect(fn () => ($this->forceDeleteOption)(1))->toThrow(EntityNotFound::class);

    ($this->removeOption)(1);
    ($this->forceDeleteOption)(1);

    expect($this->files->paths())->toBe([])
        ->and($this->groups->findTrashedOption(1))->toBeNull();
    expect(fn () => ($this->restoreOption)(1))->toThrow(EntityNotFound::class);
});

it('deletes a group forever with the images of all its options, also of deleted ones', function (): void {
    ($this->removeOption)(1);
    expect(fn () => ($this->forceDeleteGroup)(1))->toThrow(EntityNotFound::class);

    $this->groups->delete(1);
    ($this->forceDeleteGroup)(1);

    expect($this->groups->findTrashed(1))->toBeNull()
        ->and($this->files->paths())->toBe([]);
});

it('does not delete a group forever while a product has it', function (): void {
    $this->groups->delete(1);
    $this->attachments->save(ProductOptionGroup::attach(10, 1, SelectionRules::exactlyOne(), SortOrder::first()));

    expect(fn () => ($this->forceDeleteGroup)(1))->toThrow(OptionGroupInUse::class);
    expect($this->groups->findTrashed(1))->not->toBeNull()
        ->and($this->files->paths())->toHaveCount(1);
});
