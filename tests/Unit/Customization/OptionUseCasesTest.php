<?php

declare(strict_types=1);

use App\Modules\Customization\Application\DTOs\AddOptionInput;
use App\Modules\Customization\Application\DTOs\ChangeOptionImageInput;
use App\Modules\Customization\Application\DTOs\ImageUpload;
use App\Modules\Customization\Application\DTOs\OptionItemInput;
use App\Modules\Customization\Application\DTOs\ReorderOptionsInput;
use App\Modules\Customization\Application\DTOs\UpdateOptionInput;
use App\Modules\Customization\Application\Services\OptionImages;
use App\Modules\Customization\Application\UseCases\Options\AddOption;
use App\Modules\Customization\Application\UseCases\Options\ChangeOptionImage;
use App\Modules\Customization\Application\UseCases\Options\RemoveOption;
use App\Modules\Customization\Application\UseCases\Options\ReorderOptions;
use App\Modules\Customization\Application\UseCases\Options\ToggleOptionAvailable;
use App\Modules\Customization\Application\UseCases\Options\UpdateOption;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Tests\Unit\Catalog\Fakes\InMemoryFileStorage;
use Tests\Unit\Catalog\Fakes\RecordingImageProcessor;
use Tests\Unit\Customization\Fakes\InMemoryOptionGroupRepository;
use Tests\Unit\Identity\PassThroughTransactionManager;

/**
 * Runs the action and returns the form errors it caused.
 *
 * @return array<string, list<string>>
 */
function optionFormErrors(Closure $action): array
{
    try {
        $action();
    } catch (ValidationFailed $failure) {
        return $failure->errors();
    }

    throw new RuntimeException('Expected a form error, but it worked.');
}

/*
| Group 1 "Crust": Thin ৳0 (option 1), Stuffed ৳150 at size 2 (option 2).
*/
beforeEach(function (): void {
    $this->groups = new InMemoryOptionGroupRepository;
    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::first()));
    $crust->addOption(Option::withPricesPerSize('Stuffed', [2 => Money::fromTaka('150')], SortOrder::fromInt(20)));
    $this->groups->save($crust);

    $this->transactions = new PassThroughTransactionManager;
    $this->files = new InMemoryFileStorage;
    $this->add = fn (string $name, PriceMode $mode = PriceMode::Flat, ?string $price = '60') => (new AddOption($this->groups, $this->transactions))
        ->handle(new AddOptionInput(1, $name, true, $mode, $price));
    $this->update = fn (int $id, string $name, PriceMode $mode, ?string $price) => (new UpdateOption($this->groups, $this->transactions))
        ->handle(new UpdateOptionInput(1, $id, $name, true, $mode, $price));
    $this->changeImage = fn (?string $contents) => (new ChangeOptionImage($this->groups, new OptionImages($this->files, new RecordingImageProcessor)))
        ->handle(new ChangeOptionImageInput(1, 1, $contents === null ? null : new ImageUpload($contents)));
});

it('adds a flat-priced option at the end of the group', function (): void {
    ($this->add)('Pan');

    $pan = $this->groups->find(1)->options()[2];

    expect($pan->name())->toBe('Pan')
        ->and($pan->isVeg())->toBeTrue()
        ->and($pan->isAvailable())->toBeTrue()
        ->and($pan->flatPrice()->poisha())->toBe(6_000)
        ->and($pan->sort()->value())->toBe(30);
});

it('adds a per-size option without prices, so it is not offered yet', function (): void {
    ($this->add)('Cheese-filled', PriceMode::PerSize, null);

    $option = $this->groups->find(1)->options()[2];

    expect($option->priceMode())->toBe(PriceMode::PerSize)
        ->and($option->priceFor(2))->toBeNull();
});

it('asks for a valid price when the price is flat', function (): void {
    expect(optionFormErrors(fn () => ($this->add)('Pan', PriceMode::Flat, null)))->toHaveKey('flat_price')
        ->and(optionFormErrors(fn () => ($this->add)('Pan', PriceMode::Flat, 'cheap')))->toHaveKey('flat_price')
        ->and($this->groups->find(1)->options())->toHaveCount(2);
});

it('refuses a second option with the same name, under the name field', function (): void {
    expect(optionFormErrors(fn () => ($this->add)(' thin ')))->toHaveKey('name')
        ->and(optionFormErrors(fn () => ($this->update)(2, 'THIN', PriceMode::PerSize, null)))->toHaveKey('name');
});

it('changes name, veg mark and price mode, and keeps the other prices', function (): void {
    ($this->update)(2, 'Stuffed crust', PriceMode::Flat, '120');
    $flat = $this->groups->find(1)->option(2);

    expect($flat->name())->toBe('Stuffed crust')
        ->and($flat->isVeg())->toBeTrue()
        ->and($flat->priceFor(2)->poisha())->toBe(12_000);

    ($this->update)(2, 'Stuffed crust', PriceMode::PerSize, null);

    expect($this->groups->find(1)->option(2)->priceFor(2)->poisha())->toBe(15_000);
});

it('removes an option', function (): void {
    (new RemoveOption($this->groups, $this->transactions))->handle(new OptionItemInput(1, 1));

    expect($this->groups->find(1)->option(1))->toBeNull()
        ->and($this->groups->find(1)->options())->toHaveCount(1);
});

it('saves a new order of the options and refuses an incomplete one', function (): void {
    $reorder = new ReorderOptions($this->groups, $this->transactions);

    $reorder->handle(new ReorderOptionsInput(1, [2, 1]));

    expect($this->groups->find(1)->options()[0]->name())->toBe('Stuffed')
        ->and(optionFormErrors(fn () => $reorder->handle(new ReorderOptionsInput(1, [2]))))->toHaveKey('ids');
});

it('marks an option sold out and available again', function (): void {
    $toggle = new ToggleOptionAvailable($this->groups, $this->transactions);

    expect($toggle->handle(new OptionItemInput(1, 1)))->toBeFalse()
        ->and($this->groups->find(1)->option(1)->isAvailable())->toBeFalse()
        ->and($toggle->handle(new OptionItemInput(1, 1)))->toBeTrue()
        ->and($this->groups->find(1)->option(1)->isAvailable())->toBeTrue();
});

it('stores a small square image, replaces it and removes it, files included', function (): void {
    ($this->changeImage)('first');
    $first = $this->groups->find(1)->option(1)->imagePath();

    expect($this->files->contents($first))->toBe('webp(thumbnail-400(first))');

    ($this->changeImage)('second');
    $second = $this->groups->find(1)->option(1)->imagePath();

    expect($this->files->paths())->toBe([$second])
        ->and($second)->not->toBe($first);

    ($this->changeImage)(null);

    expect($this->groups->find(1)->option(1)->imagePath())->toBeNull()
        ->and($this->files->paths())->toBe([]);
});

it('deletes the new image file again when saving fails, and keeps the old one', function (): void {
    ($this->changeImage)('first');
    $first = $this->groups->find(1)->option(1)->imagePath();
    $this->groups->failNextSave();

    expect(fn () => ($this->changeImage)('second'))->toThrow(RuntimeException::class);
    expect($this->files->paths())->toBe([$first])
        ->and($this->groups->find(1)->option(1)->imagePath())->toBe($first);
});

it('reports an unknown group or option', function (): void {
    $remove = new RemoveOption($this->groups, $this->transactions);
    $toggle = new ToggleOptionAvailable($this->groups, $this->transactions);

    expect(fn () => $remove->handle(new OptionItemInput(99, 1)))->toThrow(EntityNotFound::class);
    expect(fn () => $remove->handle(new OptionItemInput(1, 99)))->toThrow(EntityNotFound::class);
    expect(fn () => $toggle->handle(new OptionItemInput(1, 99)))->toThrow(EntityNotFound::class);
    expect(fn () => ($this->update)(99, 'Pan', PriceMode::Flat, '10'))->toThrow(EntityNotFound::class);
});
