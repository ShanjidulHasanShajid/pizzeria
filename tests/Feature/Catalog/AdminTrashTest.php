<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;

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
