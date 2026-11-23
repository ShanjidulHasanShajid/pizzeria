<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->groups = app(OptionGroupRepository::class);
    $this->saveGroup = fn (string $name, int $sort = 10): int => $this->groups->save(
        OptionGroup::create($name, "Choose: {$name}", SortOrder::fromInt($sort)),
    );
    $this->attach = fn (int $groupId): int => app(ProductOptionGroupRepository::class)->save(
        ProductOptionGroup::attach(ProductModel::factory()->create()->id, $groupId, SelectionRules::upTo(1), SortOrder::first()),
    );
});

it('lists the groups in sort order with their counts', function (): void {
    ($this->saveGroup)('Dips', 20);
    $crust = OptionGroup::create('Crust', 'Your crust', SortOrder::fromInt(10));
    $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::first()));
    ($this->attach)($this->groups->save($crust));

    $this->get('/admin/option-groups')
        ->assertOk()
        ->assertSeeInOrder(['Crust', '1 option', '1 product', 'Dips', '0 options', '0 products']);
});

it('creates a group and opens its edit page to add options', function (): void {
    $this->get('/admin/option-groups/create')->assertOk();

    $response = $this->post('/admin/option-groups', ['name' => 'Crust', 'display_title' => 'Choose your crust', 'description' => '']);

    $group = OptionGroupModel::query()->where('name', 'Crust')->firstOrFail();
    $response->assertRedirect("/admin/option-groups/{$group->id}/edit")->assertSessionHas('success');

    expect($group->display_title)->toBe('Choose your crust')
        ->and($group->description)->toBeNull()
        ->and($group->is_active)->toBeTrue();
});

it('requires a name and a title', function (): void {
    $this->post('/admin/option-groups', ['name' => '', 'display_title' => ''])->assertSessionHasErrors(['name', 'display_title']);
    $this->post('/admin/option-groups', ['name' => str_repeat('x', 101), 'display_title' => 'Ok'])->assertSessionHasErrors('name');

    expect(OptionGroupModel::query()->count())->toBe(0);
});

it('shows the edit page with the option manager and saves the texts', function (): void {
    $id = ($this->saveGroup)('Crust');

    $this->get("/admin/option-groups/{$id}/edit")
        ->assertOk()
        ->assertSee('Edit Crust')
        ->assertSeeLivewire('customization.admin.option-manager');

    $this->put("/admin/option-groups/{$id}", ['name' => 'Pizza crusts', 'display_title' => 'Your crust', 'description' => 'Baked fresh.', 'is_active' => '0'])
        ->assertRedirect('/admin/option-groups');

    $group = $this->groups->find($id);

    expect($group->name())->toBe('Pizza crusts')
        ->and($group->description())->toBe('Baked fresh.')
        ->and($group->isActive())->toBeFalse();
});

it('moves an unattached group to the trash and restores it', function (): void {
    $id = ($this->saveGroup)('Crust');

    $this->delete("/admin/option-groups/{$id}")->assertSessionHas('success');
    $this->assertSoftDeleted('option_groups', ['id' => $id]);

    $this->post("/admin/option-groups/{$id}/restore")->assertSessionHas('success');
    $this->assertNotSoftDeleted('option_groups', ['id' => $id]);
});

it('refuses to delete a group that is attached to a product', function (): void {
    $id = ($this->saveGroup)('Crust');
    ($this->attach)($id);

    $this->delete("/admin/option-groups/{$id}")->assertSessionHas('error');

    $this->assertNotSoftDeleted('option_groups', ['id' => $id]);
});

it('saves a dragged order', function (): void {
    $crust = ($this->saveGroup)('Crust', 10);
    $dips = ($this->saveGroup)('Dips', 20);

    $this->postJson('/admin/option-groups/reorder', ['ids' => [$dips, $crust]])->assertNoContent();

    expect($this->groups->find($dips)->sort()->value())->toBe(10)
        ->and($this->groups->find($crust)->sort()->value())->toBe(20);
});

it('answers 404 for an unknown or deleted group', function (): void {
    $id = ($this->saveGroup)('Old');
    $this->groups->delete($id);

    $this->get("/admin/option-groups/{$id}/edit")->assertNotFound();
    $this->get("/admin/option-groups/{$id}/prices")->assertNotFound();
    $this->put('/admin/option-groups/424242', ['name' => 'X', 'display_title' => 'X'])->assertNotFound();
    $this->delete('/admin/option-groups/424242')->assertNotFound();
});

it('does not let staff change option groups', function (): void {
    $id = ($this->saveGroup)('Crust');
    $this->actingAs(User::factory()->staff()->create());

    $this->post('/admin/option-groups', ['name' => 'X', 'display_title' => 'X'])->assertForbidden();
    $this->put("/admin/option-groups/{$id}", ['name' => 'X', 'display_title' => 'X'])->assertForbidden();
    $this->delete("/admin/option-groups/{$id}")->assertForbidden();

    expect($this->groups->find($id)->name())->toBe('Crust');
});
