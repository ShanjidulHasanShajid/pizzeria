<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->groups = app(OptionGroupRepository::class);
    $this->medium = SizeTierModel::factory()->create(['name' => 'Medium', 'sort' => 10])->id;
    $this->large = SizeTierModel::factory()->create(['name' => 'Large', 'sort' => 20])->id;

    $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
    $crust->addOption(Option::withPricesPerSize('Stuffed', [$this->medium => Money::fromTaka('150')], SortOrder::fromInt(10)));
    $crust->addOption(Option::withFlatPrice('Thin', Money::fromTaka('25'), SortOrder::fromInt(20)));
    $this->group = $this->groups->save($crust);
    [$this->stuffed, $this->thin] = array_map(fn (Option $option): int => $option->id(), $this->groups->find($this->group)->options());
    $this->url = "/admin/option-groups/{$this->group}/prices";
});

it('shows the sizes as columns, the per-size options as rows and the flat prices below', function (): void {
    $this->get($this->url)
        ->assertOk()
        ->assertSeeInOrder(['Medium', 'Large', 'Stuffed', 'Same price for every size', 'Thin', '25.00'])
        ->assertSee("prices[{$this->stuffed}][{$this->medium}]", false)
        ->assertSee('value="150.00"', false)
        ->assertDontSee("prices[{$this->thin}]", false);
});

it('saves the matrix: empty is not offered, zero is free', function (): void {
    $this->put($this->url, ['prices' => [$this->stuffed => [$this->medium => '', $this->large => '0']]])
        ->assertRedirect($this->url)
        ->assertSessionHas('success');

    $stuffed = $this->groups->find($this->group)->option($this->stuffed);

    expect($stuffed->priceFor($this->medium))->toBeNull()
        ->and($stuffed->priceFor($this->large)->isZero())->toBeTrue();
});

it('shows an error under the wrong cell and saves nothing', function (): void {
    $this->put($this->url, ['prices' => [$this->stuffed => [$this->medium => '175', $this->large => 'abc']]])
        ->assertSessionHasErrors("prices.{$this->stuffed}.{$this->large}");
    $this->put($this->url, ['prices' => [$this->stuffed => [424242 => '10']]])
        ->assertSessionHasErrors("prices.{$this->stuffed}.424242");
    $this->put($this->url, ['prices' => [$this->thin => [$this->medium => '10']]])
        ->assertSessionHasErrors("prices.{$this->thin}");

    expect($this->groups->find($this->group)->option($this->stuffed)->priceFor($this->medium)->poisha())->toBe(15_000);
});

it('does not let staff see or change prices', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->get($this->url)->assertForbidden();
    $this->put($this->url, ['prices' => [$this->stuffed => [$this->medium => '1']]])->assertForbidden();

    expect($this->groups->find($this->group)->option($this->stuffed)->priceFor($this->medium)->poisha())->toBe(15_000);
});
