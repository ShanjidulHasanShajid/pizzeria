<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\SizeTier as SizeTierModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * What the product form posts, with sensible defaults. Change only what a test is about.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function productPayload(int $categoryId, array $changes = []): array
{
    return [
        'category_id' => $categoryId,
        'name' => 'Margherita',
        'slug' => '',
        'short_description' => 'Tomato and mozzarella.',
        'description' => '',
        'lead_time_class' => 'made_to_order',
        'lead_time_minutes' => '20',
        'is_new' => '0',
        'is_hot' => '0',
        'is_published' => '1',
        'is_available' => '1',
        'variants' => [
            ['id' => '', 'label' => 'Personal', 'price' => '450', 'size_tier_id' => '', 'is_available' => '1'],
            ['id' => '', 'label' => 'Large', 'price' => '1250.50', 'size_tier_id' => '', 'is_available' => '1'],
        ],
        'default_variant' => '0',
        ...$changes,
    ];
}

beforeEach(function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->category = CategoryModel::factory()->create(['name' => 'Pizza']);
});

describe('the list', function (): void {
    it('shows products with their price range, and filters by search, category and trash', function (): void {
        ProductModel::factory()->for($this->category)
            ->has(ProductVariantModel::factory()->asDefault()->state(['price' => 45_000]), 'variants')
            ->has(ProductVariantModel::factory()->state(['price' => 125_000]), 'variants')
            ->create(['name' => 'Quattro Formaggi']);
        ProductModel::factory()->create(['name' => 'Garlic Bread']);
        ProductModel::factory()->for($this->category)->create(['name' => 'Old Pizza'])->delete();

        $this->get('/admin/products')->assertOk()->assertSee('Quattro Formaggi')->assertSee('Garlic Bread')->assertSee('৳450')->assertDontSee('Old Pizza');
        $this->get('/admin/products?q=garlic')->assertSee('Garlic Bread')->assertDontSee('Quattro Formaggi');
        $this->get("/admin/products?category={$this->category->id}")->assertSee('Quattro Formaggi')->assertDontSee('Garlic Bread');
        $this->get('/admin/products?status=trashed')->assertSee('Old Pizza')->assertSee('Restore')->assertDontSee('Garlic Bread');
    });

    it('keeps the filters in the page links', function (): void {
        ProductModel::factory()->count(21)->for($this->category)->create();

        $this->get("/admin/products?category={$this->category->id}")
            ->assertOk()
            ->assertSee("category={$this->category->id}&amp;page=2", false);
    });
});

describe('creating and editing', function (): void {
    it('shows the create form with the sizes and tags to choose from', function (): void {
        SizeTierModel::factory()->create(['name' => 'Jumbo']);
        TagModel::factory()->create(['name' => 'Smoky']);

        $this->get('/admin/products/create')->assertOk()->assertSee('Jumbo')->assertSee('Smoky');
    });

    it('creates a product with its variants, default and tags, then opens the edit page', function (): void {
        $large = SizeTierModel::factory()->create();
        $veg = TagModel::factory()->create();

        $response = $this->post('/admin/products', productPayload($this->category->id, [
            'variants' => [
                ['id' => '', 'label' => 'Personal', 'price' => '450', 'size_tier_id' => '', 'is_available' => '1'],
                ['id' => '', 'label' => 'Large', 'price' => '1250.50', 'size_tier_id' => (string) $large->id, 'is_available' => '0'],
            ],
            'default_variant' => '1',
            'tags' => [(string) $veg->id],
        ]));

        $product = ProductModel::query()->where('name', 'Margherita')->firstOrFail();
        $response->assertRedirect("/admin/products/{$product->id}/edit");

        $variants = $product->variants()->orderBy('sort')->get();

        expect($product->slug)->toBe('margherita')
            ->and($product->is_published)->toBeTrue()
            ->and($variants->pluck('label')->all())->toBe(['Personal', 'Large'])
            ->and($variants[1]->price)->toBe(125_050)
            ->and($variants[1]->is_default)->toBeTrue()
            ->and($variants[1]->is_available)->toBeFalse()
            ->and($variants[1]->size_tier_id)->toBe($large->id)
            ->and($product->tags()->pluck('tags.id')->all())->toBe([$veg->id]);

        $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('Edit Margherita')->assertSee('1250.50');
    });

    it('checks every variant row', function (): void {
        $this->post('/admin/products', productPayload($this->category->id, ['variants' => []]))
            ->assertSessionHasErrors('variants');

        $this->post('/admin/products', productPayload($this->category->id, ['variants' => [
            ['label' => 'Large', 'price' => '12.345'],
            ['label' => 'large', 'price' => '1300'],
        ]]))->assertSessionHasErrors(['variants.0.price', 'variants.1.label']);

        expect(ProductModel::query()->count())->toBe(0);
    });

    it('saves edited, new and removed variants in one go and keeps the ids of edited rows', function (): void {
        $this->post('/admin/products', productPayload($this->category->id));
        $product = ProductModel::query()->where('name', 'Margherita')->firstOrFail();
        [$personal, $large] = $product->variants()->orderBy('sort')->get()->all();

        $this->put("/admin/products/{$product->id}", productPayload($this->category->id, [
            'variants' => [
                ['id' => (string) $large->id, 'label' => 'Large', 'price' => '1300', 'size_tier_id' => '', 'is_available' => '1'],
                ['id' => '', 'label' => 'Family', 'price' => '1700', 'size_tier_id' => '', 'is_available' => '1'],
            ],
            'default_variant' => '0',
        ]))->assertRedirect("/admin/products/{$product->id}/edit");

        $variants = $product->variants()->orderBy('sort')->get();

        expect($variants->pluck('label')->all())->toBe(['Large', 'Family'])
            ->and($variants[0]->id)->toBe($large->id)
            ->and($variants[0]->price)->toBe(130_000)
            ->and($variants[0]->is_default)->toBeTrue();
        $this->assertSoftDeleted('product_variants', ['id' => $personal->id]);
    });
});

describe('list actions', function (): void {
    it('publishes, hides and marks a product sold out from the list', function (): void {
        $product = ProductModel::factory()->create();

        $this->patch("/admin/products/{$product->id}/published")->assertSessionHas('success');
        $this->patch("/admin/products/{$product->id}/availability")->assertSessionHas('success');

        expect($product->fresh()->is_published)->toBeFalse()
            ->and($product->fresh()->is_available)->toBeFalse();
    });

    it('duplicates a product as a hidden copy and opens it', function (): void {
        $product = ProductModel::factory()->create(['name' => 'Pepperoni', 'slug' => 'pepperoni']);

        $response = $this->post("/admin/products/{$product->id}/duplicate");

        $copy = ProductModel::query()->where('name', 'Pepperoni (copy)')->firstOrFail();
        $response->assertRedirect("/admin/products/{$copy->id}/edit");

        expect($copy->slug)->toBe('pepperoni-copy')
            ->and($copy->is_published)->toBeFalse()
            ->and($copy->variants()->count())->toBe($product->variants()->count());
    });

    it('moves a product to the trash and restores it', function (): void {
        $product = ProductModel::factory()->create();

        $this->delete("/admin/products/{$product->id}")->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->post("/admin/products/{$product->id}/restore")->assertSessionHas('success');
        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    });

    it('lets only a super admin delete forever, with the variant rows and the image files', function (): void {
        $product = ProductModel::factory()->create();
        Storage::disk('public')->put('products/a.webp', 'original');
        Storage::disk('public')->put('products/thumbs/a.webp', 'thumbnail');
        ProductImageModel::factory()->for($product)->main()->create(['path' => 'products/a.webp', 'thumbnail_path' => 'products/thumbs/a.webp']);
        $product->delete();

        $this->delete("/admin/products/{$product->id}/force")->assertForbidden();

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->delete("/admin/products/{$product->id}/force")->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing(['products/a.webp', 'products/thumbs/a.webp']);
    });

    it('does not let staff change products', function (): void {
        $product = ProductModel::factory()->create();
        $this->actingAs(User::factory()->staff()->create());

        $this->patch("/admin/products/{$product->id}/published")->assertForbidden();

        expect($product->fresh()->is_published)->toBeTrue();
    });
});
