<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $this->items = app(ContentItemRepository::class);
    $this->locations = app(LocationRepository::class);

    // Saves one item of the type and moves it to the Trash.
    $this->trashed = function (ContentType $type, array $values): int {
        $id = $this->items->save(ContentItem::create($type, $values, DateRange::always(), true, SortOrder::first()));
        $this->items->delete($type, $id);

        return $id;
    };
    $this->trashedLocation = function (string $name): int {
        $details = LocationDetails::fromInput($name, 'Road 27', '', '16247', '', '', '', true);
        $id = $this->locations->save(Location::create($details, OpeningHours::alwaysClosed(), true, SortOrder::first()));
        $this->locations->delete($id);

        return $id;
    };
});

dataset('deleted content', [
    'hero slide' => [ContentType::HeroSlide, ['title' => 'Old slide', 'image' => 'a.webp'], 'Old slide'],
    'banner' => [ContentType::Banner, ['title' => 'Old banner', 'image' => 'a.webp'], 'Old banner'],
    'trust badge' => [ContentType::TrustBadge, ['icon' => 'clock', 'title' => 'Old badge'], 'Old badge'],
    'photo with a caption' => [ContentType::GalleryImage, ['image' => 'a.webp', 'caption' => 'Old photo'], 'Old photo'],
    'photo without a caption' => [ContentType::GalleryImage, ['image' => 'a.webp'], 'Gallery image '],
]);

it('shows deleted content on its tab and restores it', function (ContentType $type, array $values, string $name): void {
    $id = ($this->trashed)($type, $values);
    ($this->trashedLocation)('Old branch');

    $this->get('/admin/trash')->assertOk()->assertSee("{$type->label()} (1)")->assertSee($name)->assertSee('Old branch');
    $this->get("/admin/trash?type={$type->value}")->assertOk()->assertSee($name)->assertDontSee('Old branch');

    $this->from("/admin/trash?type={$type->value}")->post("/admin/{$type->value}/{$id}/restore")
        ->assertRedirect("/admin/trash?type={$type->value}")->assertSessionHas('success');

    expect($this->items->find($type, $id))->not->toBeNull();
    $this->get('/admin/trash')->assertSee("{$type->label()} (0)");
})->with('deleted content');

it('restores a location', function (): void {
    $id = ($this->trashedLocation)('Old branch');

    $this->get('/admin/trash?type=location')->assertOk()->assertSee('Locations (1)')->assertSee('Old branch');
    $this->post("/admin/locations/{$id}/restore")->assertSessionHas('success');

    expect($this->locations->find($id))->not->toBeNull();
});

it('deletes an item and its files forever, for super admins', function (): void {
    Storage::disk('public')->put('content/hero-slides/wide.webp', 'x');
    Storage::disk('public')->put('content/hero-slides/tall.webp', 'x');
    $id = ($this->trashed)(ContentType::HeroSlide, ['title' => 'Old slide', 'image' => 'content/hero-slides/wide.webp', 'mobile_image' => 'content/hero-slides/tall.webp']);

    $this->get('/admin/trash')->assertDontSee('Delete forever');
    $this->delete("/admin/hero-slides/{$id}/force")->assertForbidden();
    Storage::disk('public')->assertExists('content/hero-slides/wide.webp');

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->get('/admin/trash')->assertSee('Delete forever')->assertSee('its image files are deleted for good');
    $this->delete("/admin/hero-slides/{$id}/force")->assertSessionHas('success');

    $this->assertDatabaseCount('hero_slides', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('deletes a location forever, but nothing that is not in the Trash', function (): void {
    $deleted = ($this->trashedLocation)('Old branch');
    $kept = ($this->trashedLocation)('New branch');
    $this->locations->restore($kept);
    $banner = ($this->trashed)(ContentType::Banner, ['title' => 'Banner', 'image' => 'a.webp']);
    $this->items->restore(ContentType::Banner, $banner);
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->delete("/admin/locations/{$deleted}/force")->assertSessionHas('success');
    $this->delete("/admin/locations/{$kept}/force")->assertNotFound();
    $this->delete("/admin/banners/{$banner}/force")->assertNotFound();

    $this->assertDatabaseMissing('locations', ['id' => $deleted]);
    $this->assertDatabaseHas('locations', ['id' => $kept]);
    $this->assertDatabaseHas('banners', ['id' => $banner]);
});
