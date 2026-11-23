<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Customization\Presentation\Livewire\Admin\ProductOptions;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->product = ProductModel::factory()->create();
    $this->attachments = app(ProductOptionGroupRepository::class);
    $groups = app(OptionGroupRepository::class);
    $this->crust = $groups->save(OptionGroup::create('Crust', 'Crust', SortOrder::fromInt(10)));
    $this->dips = $groups->save(OptionGroup::create('Dips', 'Dips', SortOrder::fromInt(20)));

    $this->attach = fn (int $groupId, int $sort = 10, ?int $productId = null): int => $this->attachments->save(ProductOptionGroup::attach(
        $productId ?? $this->product->id, $groupId, SelectionRules::exactlyOne(), SortOrder::fromInt($sort),
    ));
    $this->component = fn () => Livewire::test(ProductOptions::class, ['productId' => $this->product->id]);
    $this->groupIds = fn (): array => array_map(
        fn (ProductOptionGroup $attachment): int => $attachment->optionGroupId(),
        $this->attachments->forProduct($this->product->id),
    );
});

it('stands on the edit page of a product, but not of a combo', function (): void {
    $combo = ProductModel::factory()->combo()->create();

    $this->get("/admin/products/{$this->product->id}/edit")->assertOk()->assertSeeLivewire('customization.admin.product-options');
    $this->get("/admin/products/{$combo->id}/edit")->assertOk()->assertDontSeeLivewire('customization.admin.product-options');
});

it('attaches a group with rules and empties the form', function (): void {
    ($this->component)()
        ->set('new.optionGroupId', (string) $this->dips)
        ->set('new.maximum', '3')
        ->set('new.freeCount', '1')
        ->call('attach')
        ->assertHasNoErrors()
        ->assertSet('new.optionGroupId', '')
        ->assertSee('Option group attached.');

    $attachment = $this->attachments->forProduct($this->product->id)[0];

    expect($attachment->optionGroupId())->toBe($this->dips)
        ->and($attachment->rules()->equals(SelectionRules::upTo(3, freeCount: 1)))->toBeTrue();
});

it('asks for a group, and refuses rules that do not fit together', function (): void {
    ($this->component)()
        ->call('attach')
        ->assertHasErrors('new.optionGroupId')
        ->set('new.optionGroupId', (string) $this->dips)
        ->set('new.minimum', '2')
        ->call('attach')
        ->assertHasErrors('new.rules')
        ->set('new.maximum', '99')
        ->call('attach')
        ->assertHasErrors('new.maximum');

    expect(($this->groupIds)())->toBe([]);
});

it('refuses a group that is attached already', function (): void {
    ($this->attach)($this->crust);

    ($this->component)()
        ->set('new.optionGroupId', (string) $this->crust)
        ->call('attach')
        ->assertHasErrors('new.optionGroupId');

    expect(($this->groupIds)())->toBe([$this->crust]);
});

it('saves the rules of one group', function (): void {
    $id = ($this->attach)($this->crust);

    ($this->component)()
        ->assertSet("rows.{$id}.isRequired", true)
        ->set("rows.{$id}.isRequired", false)
        ->set("rows.{$id}.minimum", '0')
        ->set("rows.{$id}.maximum", '2')
        ->call('saveRules', $id)
        ->assertHasNoErrors()
        ->set("rows.{$id}.isRequired", true)
        ->call('saveRules', $id)
        ->assertHasErrors("rows.{$id}.rules");

    expect($this->attachments->find($id)->rules()->equals(SelectionRules::upTo(2)))->toBeTrue();
});

it('reorders and detaches groups', function (): void {
    $crust = ($this->attach)($this->crust, 10);
    $dips = ($this->attach)($this->dips, 20);

    ($this->component)()->call('reorder', [(string) $dips, (string) $crust])->assertHasNoErrors();
    expect(($this->groupIds)())->toBe([$this->dips, $this->crust]);

    ($this->component)()->call('detach', $dips)->assertHasNoErrors();
    expect(($this->groupIds)())->toBe([$this->crust]);
});

it('answers 404 for an attachment of another product', function (): void {
    $other = ($this->attach)($this->crust, 10, ProductModel::factory()->create()->id);

    ($this->component)()->call('detach', $other)->assertNotFound();

    expect($this->attachments->find($other))->not->toBeNull();
});

it('checks the gate on every action, not only on the page', function (): void {
    $id = ($this->attach)($this->crust);
    $component = ($this->component)();

    $this->actingAs(User::factory()->staff()->create());

    $component->call('detach', $id)->assertForbidden();
});
