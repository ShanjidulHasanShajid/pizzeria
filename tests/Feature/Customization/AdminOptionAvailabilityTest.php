<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->groups = app(OptionGroupRepository::class);
    $dips = OptionGroup::create('Dips', 'Dips', SortOrder::first());
    $dips->addOption(Option::withFlatPrice('Garlic mayo', Money::zero(), SortOrder::first()));
    $this->dips = $this->groups->save($dips);
    $this->garlic = $this->groups->find($this->dips)->options()[0]->id();
    $this->toggleUrl = "/admin/option-groups/{$this->dips}/options/{$this->garlic}/availability";
});

it('lists every option by group, and switches one off and on', function (): void {
    $this->get('/admin/option-groups/availability')->assertOk()->assertSeeInOrder(['Dips', 'Garlic mayo']);

    $this->from('/admin/option-groups/availability')->patch($this->toggleUrl)
        ->assertRedirect('/admin/option-groups/availability')
        ->assertSessionHas('success');
    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeFalse();

    $this->patch($this->toggleUrl);
    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});

it('answers 404 for an option of another group', function (): void {
    $other = $this->groups->save(OptionGroup::create('Crust', 'Crust', SortOrder::fromInt(20)));

    $this->patch("/admin/option-groups/{$other}/options/{$this->garlic}/availability")->assertNotFound();

    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});

it('does not let staff switch options', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->patch($this->toggleUrl)->assertForbidden();

    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});
