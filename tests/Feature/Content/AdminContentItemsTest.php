<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedContentItemRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

dataset('content types', [
    'hero slides' => [ContentType::HeroSlide, ['title' => 'Hello', 'button_text' => 'Order', 'button_link' => '/menu'], ['image']],
    'banners' => [ContentType::Banner, ['title' => 'Offer', 'link' => '/menu'], ['image']],
    'trust badges' => [ContentType::TrustBadge, ['icon' => 'clock', 'title' => 'Fast delivery', 'text' => 'In 30 minutes'], []],
    'gallery' => [ContentType::GalleryImage, ['caption' => 'Our oven'], ['image']],
]);

beforeEach(function (): void {
    Storage::fake('public');

    foreach (ContentType::cases() as $type) {
        Cache::forget(CachedContentItemRepository::cacheKey($type));
    }

    $this->actingAs(User::factory()->admin()->create());
    $this->items = app(ContentItemRepository::class);
    $this->saveBanner = fn (string $title, int $sort = 10): int => $this->items->save(
        ContentItem::create(ContentType::Banner, ['title' => $title, 'image' => 'content/banners/a.webp'], DateRange::always(), true, SortOrder::fromInt($sort)),
    );
});

it('shows the list and the form, and creates, edits, switches, deletes and restores an item', function (ContentType $type, array $values, array $images): void {
    $url = "/admin/{$type->value}";
    $files = fn (): array => array_fill_keys($images, UploadedFile::fake()->image('photo.jpg', 800, 600));

    $this->get($url)->assertOk()->assertSee($type->label());
    $this->get("{$url}/create")->assertOk();
    $this->post($url, [...$values, ...$files(), 'is_active' => '1'])->assertRedirect($url)->assertSessionHas('success');

    $item = $this->items->all($type)[0];

    foreach ($values as $field => $value) {
        expect($item->value($field))->toBe($value);
    }

    Storage::disk('public')->assertExists($item->imagePaths());

    $this->get("{$url}/{$item->id}/edit")->assertOk();
    $this->put("{$url}/{$item->id}", [...$values, 'is_active' => '0'])->assertRedirect($url);
    expect($this->items->find($type, $item->id)->isActive)->toBeFalse()
        ->and($this->items->find($type, $item->id)->imagePaths())->toBe($item->imagePaths());

    $this->patch("{$url}/{$item->id}/toggle")->assertSessionHas('success');
    expect($this->items->find($type, $item->id)->isActive)->toBeTrue();

    $this->delete("{$url}/{$item->id}")->assertSessionHas('success');
    expect($this->items->all($type))->toBe([]);
    $this->post("{$url}/{$item->id}/restore")->assertSessionHas('success');
    expect($this->items->all($type))->toHaveCount(1);
})->with('content types');

it('answers 404 for unknown items and refuses staff', function (ContentType $type): void {
    $url = "/admin/{$type->value}";

    $this->get("{$url}/424242/edit")->assertNotFound();
    $this->put("{$url}/424242", [])->assertNotFound();
    $this->patch("{$url}/424242/toggle")->assertNotFound();
    $this->delete("{$url}/424242")->assertNotFound();

    $this->actingAs(User::factory()->staff()->create());
    $this->get($url)->assertForbidden();
    $this->post($url, [])->assertForbidden();
    $this->patch("{$url}/1/toggle")->assertForbidden();
    $this->delete("{$url}/1")->assertForbidden();
    $this->post("{$url}/reorder", ['ids' => [1]])->assertForbidden();
})->with(ContentType::cases());

it('shows every error under its field and stores nothing', function (): void {
    $this->post('/admin/banners', ['title' => '', 'link' => 'javascript:alert(1)'])->assertSessionHasErrors(['title', 'link', 'image']);
    $this->post('/admin/banners', ['title' => 'Offer', 'image' => UploadedFile::fake()->image('tiny.jpg', 100, 100)])->assertSessionHasErrors('image');
    $this->post('/admin/banners', [
        'title' => 'Offer', 'image' => UploadedFile::fake()->image('a.jpg', 800, 600), 'starts_at' => '2026-12-02T10:00', 'ends_at' => '2026-12-01T10:00',
    ])->assertSessionHasErrors('ends_at');
    $this->post('/admin/trust-badges', ['icon' => 'skull', 'title' => 'Fast'])->assertSessionHasErrors('icon');

    $this->assertDatabaseCount('banners', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('saves the dates of a banner in Dhaka time and shows its state', function (): void {
    $this->post('/admin/banners', [
        'title' => 'Far away', 'image' => UploadedFile::fake()->image('a.jpg', 800, 600), 'is_active' => '1', 'starts_at' => '2099-12-01T10:30',
    ])->assertSessionHasNoErrors();

    $banner = $this->items->all(ContentType::Banner)[0];

    expect($banner->schedule->start()->format('Y-m-d H:i P'))->toBe('2099-12-01 10:30 +06:00');
    $this->get('/admin/banners')->assertSee('Scheduled')->assertSee('1 Dec 2099, 10:30 AM');
    $this->get("/admin/banners/{$banner->id}/edit")->assertSee('value="2099-12-01T10:30"', false);
});

it('saves a dragged order', function (): void {
    $first = ($this->saveBanner)('First', 10);
    $second = ($this->saveBanner)('Second', 20);

    $this->postJson('/admin/banners/reorder', ['ids' => [$second, $first]])->assertNoContent();

    $this->get('/admin/banners')->assertSeeInOrder(['Second', 'First']);
});

it('uploads several gallery photos at once', function (): void {
    $this->post('/admin/gallery/upload', ['images' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 900, 900)]])
        ->assertRedirect('/admin/gallery')
        ->assertSessionHas('success');

    expect($this->items->all(ContentType::GalleryImage))->toHaveCount(2);

    $this->post('/admin/gallery/upload', [])->assertSessionHasErrors('images');
    $this->post('/admin/gallery/upload', ['images' => [UploadedFile::fake()->image('tiny.jpg', 50, 50)]])->assertSessionHasErrors('images.0');
    $this->actingAs(User::factory()->staff()->create());
    $this->post('/admin/gallery/upload', [])->assertForbidden();

    expect($this->items->all(ContentType::GalleryImage))->toHaveCount(2);
});
