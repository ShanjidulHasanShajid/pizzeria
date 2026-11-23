<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza']);
    $this->crust = app(OptionGroupRepository::class)->save(OptionGroup::create('Crust', 'Crust', SortOrder::first()));
    $this->url = "/admin/option-groups/{$this->crust}/bulk-attach";
    $this->form = ['category_id' => $this->pizza->id, 'is_required' => '1', 'minimum' => '1', 'maximum' => '1', 'free_count' => '0'];
});

it('shows the form with the categories', function (): void {
    $this->get($this->url)->assertOk()->assertSee('Attach Crust to a category')->assertSee('Pizza');
    $this->get('/admin/option-groups/424242/bulk-attach')->assertNotFound();
});

it('attaches the group to every product of the category', function (): void {
    $products = ProductModel::factory()->count(2)->for($this->pizza)->create();
    $elsewhere = ProductModel::factory()->create();

    $this->post($this->url, $this->form)
        ->assertRedirect("/admin/option-groups/{$this->crust}/edit")
        ->assertSessionHas('success');

    $attachments = app(ProductOptionGroupRepository::class);

    expect($attachments->forProduct($products[0]->id)[0]->rules()->isRequired())->toBeTrue()
        ->and($attachments->forProduct($products[1]->id))->toHaveCount(1)
        ->and($attachments->forProduct($elsewhere->id))->toBe([]);
});

it('refuses missing numbers, rules that do not fit together and an unknown category', function (): void {
    ProductModel::factory()->for($this->pizza)->create();

    $this->post($this->url, [])->assertSessionHasErrors(['category_id', 'minimum', 'maximum', 'free_count']);
    $this->post($this->url, [...$this->form, 'minimum' => '0'])->assertSessionHasErrors('rules');
    $this->post($this->url, [...$this->form, 'category_id' => 424242])->assertSessionHasErrors('category_id');

    $this->assertDatabaseCount('product_option_group', 0);
});

it('does not let staff attach groups', function (): void {
    ProductModel::factory()->for($this->pizza)->create();
    $this->actingAs(User::factory()->staff()->create());

    $this->get($this->url)->assertForbidden();
    $this->post($this->url, $this->form)->assertForbidden();

    $this->assertDatabaseCount('product_option_group', 0);
});
