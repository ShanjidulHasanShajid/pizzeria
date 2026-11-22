<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\KitchenStation as KitchenStationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    // A fake "public" disk: files go to a temporary folder that is emptied after each test.
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->station = KitchenStationModel::factory()->create();
});

describe('the list', function (): void {
    it('shows top-level categories in order and the sub-categories of each parent', function (): void {
        $beverages = CategoryModel::factory()->create(['name' => 'Beverages', 'sort' => 20]);
        CategoryModel::factory()->create(['name' => 'Antipasti', 'sort' => 10]);
        CategoryModel::factory()->childOf($beverages)->create(['name' => 'Lassi']);

        $this->get('/admin/categories')
            ->assertOk()
            ->assertSeeInOrder(['Antipasti', 'Beverages', 'Sub-categories of Beverages', 'Lassi']);
    });

    it('searches and filters by status, and lists the trash', function (): void {
        CategoryModel::factory()->create(['name' => 'Antipasti']);
        CategoryModel::factory()->inactive()->create(['name' => 'Bruschetta']);
        CategoryModel::factory()->create(['name' => 'Calzone'])->delete();

        $this->get('/admin/categories?q=anti')->assertSee('Antipasti')->assertDontSee('Bruschetta');
        $this->get('/admin/categories?status=inactive')->assertSee('Bruschetta')->assertDontSee('Antipasti');
        $this->get('/admin/categories?status=trashed')->assertSee('Calzone')->assertSee('Restore')->assertDontSee('Antipasti');
    });
});

describe('creating and editing', function (): void {
    it('creates a category with a slug from the name and an uploaded image', function (): void {
        $this->get('/admin/categories/create')->assertOk()->assertSee($this->station->name);

        $this->post('/admin/categories', [
            'name' => 'Soft Drinks',
            'slug' => '',
            'parent_id' => '',
            'kitchen_station_id' => $this->station->id,
            'is_active' => '1',
            'show_in_mega_menu' => '1',
            'is_featured' => '0',
            'image' => UploadedFile::fake()->image('drinks.jpg', 800, 600),
        ])->assertRedirect('/admin/categories');

        $category = CategoryModel::query()->where('name', 'Soft Drinks')->firstOrFail();

        expect($category->slug)->toBe('soft-drinks')
            ->and($category->image_path)->toStartWith('categories/')->toEndWith('.webp')
            ->and($category->image_thumbnail_path)->toStartWith('categories/thumbs/');
        Storage::disk('public')->assertExists([$category->image_path, $category->image_thumbnail_path]);
    });

    it('refuses an image that is too small or not an image', function (): void {
        $base = ['name' => 'Soups', 'kitchen_station_id' => $this->station->id];

        $this->post('/admin/categories', [...$base, 'image' => UploadedFile::fake()->image('tiny.jpg', 100, 100)])
            ->assertSessionHasErrors('image');
        $this->post('/admin/categories', [...$base, 'image' => UploadedFile::fake()->create('menu.pdf', 50, 'application/pdf')])
            ->assertSessionHasErrors('image');

        expect(CategoryModel::query()->count())->toBe(0);
    });

    it('creates a sub-category, but refuses a sub-category as parent', function (): void {
        $drinks = CategoryModel::factory()->create();
        $shakes = CategoryModel::factory()->childOf($drinks)->create();

        $this->post('/admin/categories', ['name' => 'Coffee', 'parent_id' => $drinks->id, 'kitchen_station_id' => $this->station->id])
            ->assertRedirect('/admin/categories');
        $this->post('/admin/categories', ['name' => 'Thick Shakes', 'parent_id' => $shakes->id, 'kitchen_station_id' => $this->station->id])
            ->assertSessionHasErrors('parent_id');

        expect(CategoryModel::query()->where('name', 'Coffee')->firstOrFail()->parent_id)->toBe($drinks->id)
            ->and(CategoryModel::query()->where('name', 'Thick Shakes')->exists())->toBeFalse();
    });

    it('replaces the image on edit and deletes the old files', function (): void {
        $this->post('/admin/categories', [
            'name' => 'Pizza', 'kitchen_station_id' => $this->station->id, 'image' => UploadedFile::fake()->image('old.jpg', 800, 800),
        ]);
        $category = CategoryModel::query()->where('name', 'Pizza')->firstOrFail();
        $oldFiles = [$category->image_path, $category->image_thumbnail_path];

        $this->get("/admin/categories/{$category->id}/edit")->assertOk()->assertSee('Edit Pizza');

        $this->put("/admin/categories/{$category->id}", [
            'name' => 'Pizza', 'kitchen_station_id' => $this->station->id, 'is_active' => '1', 'show_in_mega_menu' => '1',
            'image' => UploadedFile::fake()->image('new.jpg', 900, 900),
        ])->assertRedirect('/admin/categories');

        $category->refresh();
        Storage::disk('public')->assertMissing($oldFiles);
        Storage::disk('public')->assertExists([$category->image_path, $category->image_thumbnail_path]);
    });

    it('removes the image when "Remove image" was clicked', function (): void {
        $this->post('/admin/categories', [
            'name' => 'Pasta', 'kitchen_station_id' => $this->station->id, 'image' => UploadedFile::fake()->image('pasta.jpg', 800, 800),
        ]);
        $category = CategoryModel::query()->where('name', 'Pasta')->firstOrFail();
        $oldFile = $category->image_path;

        $this->put("/admin/categories/{$category->id}", [
            'name' => 'Pasta', 'kitchen_station_id' => $this->station->id, 'remove_image' => '1',
        ])->assertRedirect('/admin/categories');

        expect($category->fresh()->image_path)->toBeNull();
        Storage::disk('public')->assertMissing($oldFile);
    });
});

describe('switches, order and deleting', function (): void {
    it('switches a category off and features it from the list', function (): void {
        $category = CategoryModel::factory()->create();

        $this->patch("/admin/categories/{$category->id}/active")->assertSessionHas('success');
        $this->patch("/admin/categories/{$category->id}/featured")->assertSessionHas('success');

        expect($category->fresh()->is_active)->toBeFalse()
            ->and($category->fresh()->is_featured)->toBeTrue();
    });

    it('saves a dragged order inside one group and refuses mixed groups', function (): void {
        $pizza = CategoryModel::factory()->create(['sort' => 10]);
        $pasta = CategoryModel::factory()->create(['sort' => 20]);
        $child = CategoryModel::factory()->childOf($pizza)->create();

        $this->postJson('/admin/categories/reorder', ['ids' => [$pasta->id, $pizza->id]])->assertNoContent();
        $this->postJson('/admin/categories/reorder', ['ids' => [$child->id, $pasta->id]])->assertUnprocessable();

        expect($pasta->fresh()->sort)->toBe(10)
            ->and($pizza->fresh()->sort)->toBe(20);
    });

    it('refuses to delete a category with products or sub-categories', function (): void {
        $pizza = CategoryModel::factory()->create();
        ProductModel::factory()->for($pizza)->create();
        $drinks = CategoryModel::factory()->create();
        CategoryModel::factory()->childOf($drinks)->create();

        $this->delete("/admin/categories/{$pizza->id}")->assertSessionHas('error');
        $this->delete("/admin/categories/{$drinks->id}")->assertSessionHas('error');

        $this->assertNotSoftDeleted('categories', ['id' => $pizza->id]);
        $this->assertNotSoftDeleted('categories', ['id' => $drinks->id]);
    });

    it('moves an empty category to the trash and restores it', function (): void {
        $category = CategoryModel::factory()->create();

        $this->delete("/admin/categories/{$category->id}")->assertSessionHas('success');
        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $this->post("/admin/categories/{$category->id}/restore")->assertSessionHas('success');
        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    });

    it('lets only a super admin delete forever, and removes the image files', function (): void {
        $this->post('/admin/categories', [
            'name' => 'Soups', 'kitchen_station_id' => $this->station->id, 'image' => UploadedFile::fake()->image('soup.jpg', 800, 800),
        ]);
        $category = CategoryModel::query()->where('name', 'Soups')->firstOrFail();
        $files = [$category->image_path, $category->image_thumbnail_path];
        $category->delete();

        $this->delete("/admin/categories/{$category->id}/force")->assertForbidden();

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->delete("/admin/categories/{$category->id}/force")->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing($files);
    });

    it('does not let staff change categories', function (): void {
        $category = CategoryModel::factory()->create();
        $this->actingAs(User::factory()->staff()->create());

        $this->patch("/admin/categories/{$category->id}/active")->assertForbidden();

        expect($category->fresh()->is_active)->toBeTrue();
    });
});
