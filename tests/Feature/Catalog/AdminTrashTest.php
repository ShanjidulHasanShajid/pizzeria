<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists every deleted catalog item, newest deletion first, with a count on each tab', function (): void {
    KitchenStationModel::factory()->create(['name' => 'Old Grill', 'deleted_at' => '2026-11-01 10:00:00']);
    TagModel::factory()->create(['name' => 'Mild', 'deleted_at' => '2026-11-02 10:00:00']);
    ProductModel::factory()->create(['name' => 'Tomato Soup', 'deleted_at' => '2026-11-04 10:00:00']);
    ProductModel::factory()->create(['name' => 'Margherita']);

    $this->get('/admin/trash')
        ->assertOk()
        ->assertSeeInOrder(['Tomato Soup', 'Mild', 'Old Grill'])
        ->assertSee('4 Nov 2026, 10:00 AM')
        ->assertSee('All (3)')
        ->assertSee('Products (1)')
        ->assertDontSee('Margherita');
});

it('shows one kind per tab, and everything for an unknown tab', function (): void {
    TagModel::factory()->create(['name' => 'Mild'])->delete();
    ProductModel::factory()->create(['name' => 'Tomato Soup'])->delete();

    $this->get('/admin/trash?type=tag')->assertOk()->assertSee('Mild')->assertDontSee('Tomato Soup');
    $this->get('/admin/trash?type=nonsense')->assertOk()->assertSee('Mild')->assertSee('Tomato Soup');
});

it('restores an item and goes back to the same tab', function (): void {
    $tag = TagModel::factory()->create(['name' => 'Mild']);
    $tag->delete();

    $this->from('/admin/trash?type=tag')
        ->post("/admin/tags/{$tag->id}/restore")
        ->assertRedirect('/admin/trash?type=tag')
        ->assertSessionHas('success');

    $this->assertNotSoftDeleted('tags', ['id' => $tag->id]);
});

it('shows "Delete forever" to super admins only', function (): void {
    TagModel::factory()->create()->delete();

    $this->get('/admin/trash')->assertOk()->assertSee('Restore')->assertDontSee('Delete forever');

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->get('/admin/trash')->assertOk()->assertSee('Delete forever');
});

it('lets only a super admin delete a station, a size and a tag forever', function (): void {
    $station = KitchenStationModel::factory()->create();
    $size = SizeTierModel::factory()->create();
    $tag = TagModel::factory()->create();
    ProductModel::factory()->create()->tags()->attach($tag);
    $station->delete();
    $size->delete();
    $tag->delete();

    $this->delete("/admin/kitchen-stations/{$station->id}/force")->assertForbidden();
    $this->delete("/admin/size-tiers/{$size->id}/force")->assertForbidden();
    $this->delete("/admin/tags/{$tag->id}/force")->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->delete("/admin/kitchen-stations/{$station->id}/force")->assertSessionHas('success');
    $this->delete("/admin/size-tiers/{$size->id}/force")->assertSessionHas('success');
    $this->delete("/admin/tags/{$tag->id}/force")->assertSessionHas('success');

    $this->assertDatabaseMissing('kitchen_stations', ['id' => $station->id]);
    $this->assertDatabaseMissing('size_tiers', ['id' => $size->id]);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    $this->assertDatabaseMissing('product_tag', ['tag_id' => $tag->id]);
});

it('refuses to delete a station forever while a category in the trash uses it', function (): void {
    $station = KitchenStationModel::factory()->create();
    CategoryModel::factory()->for($station)->create()->delete();
    $station->delete();
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->delete("/admin/kitchen-stations/{$station->id}/force")->assertSessionHas('error');

    $this->assertSoftDeleted('kitchen_stations', ['id' => $station->id]);
});

it('does not restore a product while its category is in the trash', function (): void {
    $soups = CategoryModel::factory()->create(['name' => 'Soups']);
    $product = ProductModel::factory()->for($soups)->create();
    $product->delete();
    $soups->delete();

    $this->post("/admin/products/{$product->id}/restore")->assertSessionHas('error');

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

describe('option groups and options (8.35)', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        $this->groups = app(OptionGroupRepository::class);
        $crust = OptionGroup::create('Crust', 'Crust', SortOrder::first());
        $crust->addOption(Option::withFlatPrice('Thin', Money::zero(), SortOrder::fromInt(10)));
        $crust->addOption(Option::withFlatPrice('Stuffed', Money::zero(), SortOrder::fromInt(20)));
        $this->crust = $this->groups->save($crust);
        $this->thin = $this->groups->find($this->crust)->options()[0]->id();

        $this->removeThin = function (): void {
            $crust = $this->groups->find($this->crust);
            $crust->removeOption($this->thin);
            $this->groups->save($crust);
        };
    });

    it('shows deleted groups and options on their own tabs', function (): void {
        ($this->removeThin)();
        $this->groups->delete($this->groups->save(OptionGroup::create('Old dips', 'Dips', SortOrder::fromInt(20))));

        $this->get('/admin/trash')->assertOk()->assertSee('Option groups (1)')->assertSee('Options (1)')->assertSee('Thin (Crust)');
        $this->get('/admin/trash?type=option_group')->assertOk()->assertSee('Old dips')->assertDontSee('Thin (Crust)');
    });

    it('restores a group and an option, but not an option of a deleted group', function (): void {
        ($this->removeThin)();
        $this->groups->delete($this->crust);

        $this->post("/admin/options/{$this->thin}/restore")->assertSessionHas('error');
        $this->assertSoftDeleted('options', ['id' => $this->thin]);

        $this->post("/admin/option-groups/{$this->crust}/restore")->assertSessionHas('success');
        $this->post("/admin/options/{$this->thin}/restore")->assertSessionHas('success');

        expect($this->groups->find($this->crust)->options())->toHaveCount(2);
    });

    it('lets only a super admin delete a group and an option forever', function (): void {
        ($this->removeThin)();

        $this->delete("/admin/options/{$this->thin}/force")->assertForbidden();
        $this->delete("/admin/option-groups/{$this->crust}/force")->assertForbidden();

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get('/admin/trash')->assertOk()->assertSee('Delete forever');
        $this->delete("/admin/options/{$this->thin}/force")->assertSessionHas('success');
        $this->delete("/admin/option-groups/{$this->crust}/force")->assertNotFound(); // not in the Trash yet

        $this->groups->delete($this->crust);
        $this->delete("/admin/option-groups/{$this->crust}/force")->assertSessionHas('success');

        $this->assertDatabaseCount('options', 0);
        $this->assertDatabaseCount('option_groups', 0);
    });

    it('refuses to delete a group forever while a product in the trash has it', function (): void {
        $product = ProductModel::factory()->create();
        app(ProductOptionGroupRepository::class)->save(
            ProductOptionGroup::attach($product->id, $this->crust, SelectionRules::exactlyOne(), SortOrder::first()),
        );
        $product->delete();
        $this->groups->delete($this->crust);
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->delete("/admin/option-groups/{$this->crust}/force")->assertSessionHas('error');

        $this->assertSoftDeleted('option_groups', ['id' => $this->crust]);
    });
});
