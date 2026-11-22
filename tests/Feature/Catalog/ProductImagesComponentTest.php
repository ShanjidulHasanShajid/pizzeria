<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Presentation\Livewire\Admin\ProductImages;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->product = ProductModel::factory()->create();
});

it('uploads several images as WebP with thumbnails, the first one as main', function (): void {
    Livewire::test(ProductImages::class, ['productId' => $this->product->id])
        ->set('uploads', [UploadedFile::fake()->image('front.jpg', 800, 800), UploadedFile::fake()->image('side.png', 900, 700)])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('uploads', [])
        ->assertSee('2 images added.');

    $images = ProductImageModel::query()->where('product_id', $this->product->id)->orderBy('sort')->get();

    expect($images)->toHaveCount(2)
        ->and($images[0]->is_main)->toBeTrue()
        ->and($images[1]->is_main)->toBeFalse()
        ->and($images[0]->path)->toStartWith('products/')->toEndWith('.webp');
    Storage::disk('public')->assertExists([$images[0]->path, $images[0]->thumbnail_path, $images[1]->path, $images[1]->thumbnail_path]);
});

it('refuses an image that is too small', function (): void {
    Livewire::test(ProductImages::class, ['productId' => $this->product->id])
        ->set('uploads', [UploadedFile::fake()->image('tiny.jpg', 100, 100)])
        ->call('save')
        ->assertHasErrors('uploads.0');

    expect(ProductImageModel::query()->count())->toBe(0);
});

it('sets the main image, reorders, and deletes an image with its files', function (): void {
    foreach (['a', 'b'] as $index => $name) {
        Storage::disk('public')->put("products/{$name}.webp", 'original');
        Storage::disk('public')->put("products/thumbs/{$name}.webp", 'thumbnail');
        ProductImageModel::factory()->for($this->product)->create([
            'path' => "products/{$name}.webp",
            'thumbnail_path' => "products/thumbs/{$name}.webp",
            'is_main' => $index === 0,
            'sort' => ($index + 1) * 10,
        ]);
    }

    Livewire::test(ProductImages::class, ['productId' => $this->product->id])
        ->call('setMain', 'products/b.webp')
        ->call('reorder', ['products/b.webp', 'products/a.webp'])
        ->call('remove', 'products/a.webp')
        ->assertHasNoErrors();

    $images = ProductImageModel::query()->where('product_id', $this->product->id)->get();

    expect($images)->toHaveCount(1)
        ->and($images[0]->path)->toBe('products/b.webp')
        ->and($images[0]->is_main)->toBeTrue()
        ->and($images[0]->sort)->toBe(10);
    Storage::disk('public')->assertMissing(['products/a.webp', 'products/thumbs/a.webp']);
    Storage::disk('public')->assertExists('products/b.webp');
});

it('refuses an image path the product does not have', function (): void {
    Livewire::test(ProductImages::class, ['productId' => $this->product->id])
        ->call('setMain', 'products/someone-else.webp')
        ->assertHasErrors('uploads');
});

it('checks the gate on every action, not only on the page', function (): void {
    $component = Livewire::test(ProductImages::class, ['productId' => $this->product->id]);

    $this->actingAs(User::factory()->staff()->create());

    $component->call('setMain', 'products/a.webp')->assertForbidden();
});
