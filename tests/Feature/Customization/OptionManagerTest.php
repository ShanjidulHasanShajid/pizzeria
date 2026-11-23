<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Presentation\Livewire\Admin\OptionManager;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->groups = app(OptionGroupRepository::class);

    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10)));
    $crust->addOption(Option::withPricesPerSize('Stuffed', [], SortOrder::fromInt(20)));
    $this->group = $this->groups->save($crust);
    [$this->thin, $this->stuffed] = array_map(fn (Option $option): int => $option->id(), $this->groups->find($this->group)->options());

    $this->component = fn () => Livewire::test(OptionManager::class, ['optionGroupId' => $this->group]);
    $this->names = fn (): array => array_map(fn (Option $option): string => $option->name(), $this->groups->find($this->group)->options());
});

it('fills a row for every option', function (): void {
    ($this->component)()
        ->assertSet("rows.{$this->thin}.name", 'Thin')
        ->assertSet("rows.{$this->thin}.priceMode", 'flat')
        ->assertSet("rows.{$this->thin}.flatPrice", '0.00')
        ->assertSet("rows.{$this->stuffed}.priceMode", 'per_size');
});

it('adds a flat-priced option and empties the form', function (): void {
    ($this->component)()
        ->set('new.name', 'Pan')
        ->set('new.isVeg', true)
        ->set('new.flatPrice', '60.50')
        ->call('add')
        ->assertHasNoErrors()
        ->assertSet('new.name', '')
        ->assertSee('Option added.');

    $pan = $this->groups->find($this->group)->options()[2];

    expect($pan->name())->toBe('Pan')
        ->and($pan->isVeg())->toBeTrue()
        ->and($pan->flatPrice()->poisha())->toBe(6_050);
});

it('adds a per-size option without a price', function (): void {
    ($this->component)()
        ->set('new.name', 'Cheese-filled')
        ->set('new.priceMode', 'per_size')
        ->call('add')
        ->assertHasNoErrors();

    expect($this->groups->find($this->group)->options()[2]->priceMode())->toBe(PriceMode::PerSize);
});

it('asks for a name and a valid price', function (): void {
    ($this->component)()
        ->call('add')
        ->assertHasErrors(['new.name', 'new.flatPrice'])
        ->set('new.name', 'Pan')
        ->set('new.flatPrice', 'cheap')
        ->call('add')
        ->assertHasErrors('new.flatPrice')
        ->set('new.priceMode', 'free')
        ->call('add')
        ->assertHasErrors('new.priceMode');

    expect(($this->names)())->toBe(['Thin', 'Stuffed']);
});

it('shows a duplicate name under the right field', function (): void {
    ($this->component)()
        ->set('new.name', 'thin')
        ->set('new.flatPrice', '10')
        ->call('add')
        ->assertHasErrors('new.name')
        ->set("rows.{$this->stuffed}.name", 'Thin')
        ->call('saveRow', $this->stuffed)
        ->assertHasErrors("rows.{$this->stuffed}.name");
});

it('saves one row and leaves the typing in the others alone', function (): void {
    ($this->component)()
        ->set("rows.{$this->thin}.name", 'Thin & crispy')
        ->set("rows.{$this->thin}.isVeg", true)
        ->set("rows.{$this->thin}.flatPrice", '15')
        ->set("rows.{$this->stuffed}.name", 'Not saved yet')
        ->call('saveRow', $this->thin)
        ->assertHasNoErrors()
        ->assertSet("rows.{$this->stuffed}.name", 'Not saved yet');

    $thin = $this->groups->find($this->group)->option($this->thin);

    expect($thin->name())->toBe('Thin & crispy')
        ->and($thin->isVeg())->toBeTrue()
        ->and($thin->flatPrice()->poisha())->toBe(1_500)
        ->and(($this->names)())->toBe(['Thin & crispy', 'Stuffed']);
});

it('marks an option sold out, reorders and deletes', function (): void {
    ($this->component)()
        ->call('toggle', $this->thin)
        ->assertSee('The option is sold out now.')
        ->call('reorder', [(string) $this->stuffed, (string) $this->thin])
        ->assertHasNoErrors();

    expect($this->groups->find($this->group)->option($this->thin)->isAvailable())->toBeFalse()
        ->and(($this->names)())->toBe(['Stuffed', 'Thin']);

    ($this->component)()->call('remove', $this->thin)->assertHasNoErrors();

    expect(($this->names)())->toBe(['Stuffed']);
    $this->assertSoftDeleted('options', ['id' => $this->thin]);
});

it('uploads an image for one option and removes it again', function (): void {
    $component = ($this->component)()
        ->set("uploads.{$this->thin}", UploadedFile::fake()->image('thin.jpg', 800, 600))
        ->call('saveImage', $this->thin)
        ->assertHasNoErrors()
        ->assertSet('uploads', []);

    $path = $this->groups->find($this->group)->option($this->thin)->imagePath();

    expect($path)->toStartWith('options/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    $component->call('removeImage', $this->thin);

    expect($this->groups->find($this->group)->option($this->thin)->imagePath())->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('refuses an image that is too small, or no image', function (): void {
    ($this->component)()
        ->call('saveImage', $this->thin)
        ->assertHasErrors("uploads.{$this->thin}")
        ->set("uploads.{$this->thin}", UploadedFile::fake()->image('tiny.jpg', 100, 100))
        ->call('saveImage', $this->thin)
        ->assertHasErrors("uploads.{$this->thin}");

    expect($this->groups->find($this->group)->option($this->thin)->imagePath())->toBeNull();
});

it('answers 404 for an option of another group', function (): void {
    $dips = OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(20));
    $dips->addOption(Option::withFlatPrice('Garlic', Money::zero(), SortOrder::first()));
    $garlic = $this->groups->find($this->groups->save($dips))->options()[0]->id();

    ($this->component)()->call('toggle', $garlic)->assertNotFound();
    ($this->component)()->call('remove', $garlic)->assertNotFound();

    $this->assertNotSoftDeleted('options', ['id' => $garlic]);
});

it('checks the gate on every action, not only on the page', function (): void {
    $component = ($this->component)();

    $this->actingAs(User::factory()->staff()->create());

    $component->call('toggle', $this->thin)->assertForbidden();
});
