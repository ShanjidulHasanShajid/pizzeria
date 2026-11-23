<?php

declare(strict_types=1);

use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->groups = app(OptionGroupRepository::class);
    $dips = OptionGroup::create('Dips', 'Dips', SortOrder::first());
    $dips->addOption(Option::withFlatPrice('Garlic mayo', Money::zero(), SortOrder::first()));
    $this->dips = $this->groups->save($dips);
    $this->garlic = $this->groups->find($this->dips)->options()[0]->id();
    $this->toggleUrl = "/admin/option-availability/{$this->dips}/{$this->garlic}";
});

it('lists every option by group, and switches one off and on', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin/option-availability')->assertOk()->assertSeeInOrder(['Dips', 'Garlic mayo']);

    $this->from('/admin/option-availability')->patch($this->toggleUrl)
        ->assertRedirect('/admin/option-availability')
        ->assertSessionHas('success');
    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeFalse();

    $this->patch($this->toggleUrl);
    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});

it('lets kitchen staff find the page in the sidebar and switch options, but not open the option groups', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->get('/admin')->assertOk()->assertSee('Sold out today')->assertDontSee('Option Groups');
    $this->get('/admin/option-availability')->assertOk()->assertSee('Garlic mayo')->assertDontSee('/admin/option-groups');
    $this->patch($this->toggleUrl)->assertSessionHas('success');
    $this->get("/admin/option-groups/{$this->dips}/edit")->assertForbidden();

    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeFalse();
});

it('answers 404 for an option of another group', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $other = $this->groups->save(OptionGroup::create('Crust', 'Crust', SortOrder::fromInt(20)));

    $this->patch("/admin/option-availability/{$other}/{$this->garlic}")->assertNotFound();

    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});

it('does not let guests and customers switch options', function (): void {
    $this->patch($this->toggleUrl)->assertRedirect('/login');

    $this->actingAs(User::factory()->create());
    $this->patch($this->toggleUrl)->assertForbidden();

    expect($this->groups->find($this->dips)->option($this->garlic)->isAvailable())->toBeTrue();
});
