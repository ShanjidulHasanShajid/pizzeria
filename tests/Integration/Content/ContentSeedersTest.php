<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Application\Queries\SettingsQuery;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Infrastructure\Database\Seeders\ContentDevelopmentSeeder;
use App\Modules\Content\Infrastructure\Database\Seeders\ContentProductionSeeder;
use App\Modules\Content\Presentation\Support\ContentLabels;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->content = app(ContentQuery::class);
});

it('gives a new shop trust badges and a sample location that customers do not see', function (): void {
    $this->seed(ContentProductionSeeder::class);

    $badges = $this->content->visible(ContentType::TrustBadge);

    expect($badges)->toHaveCount(4);

    foreach ($badges as $badge) {
        expect(ContentLabels::ICONS)->toHaveKey($badge->value('icon'));
    }

    expect(app(LocationQuery::class)->table())->toHaveCount(1)
        ->and(app(LocationQuery::class)->active())->toBe([]);
    $this->assertDatabaseCount('settings', 0);
    $this->assertDatabaseCount('nav_items', 0);
});

it('changes nothing when it runs again, also not what the admin deleted', function (): void {
    $this->seed(ContentProductionSeeder::class);
    $items = app(ContentItemRepository::class);

    foreach ($items->all(ContentType::TrustBadge) as $badge) {
        $items->delete(ContentType::TrustBadge, (int) $badge->id);
    }

    $this->seed(ContentProductionSeeder::class);

    expect($items->all(ContentType::TrustBadge))->toBe([]);
    $this->assertDatabaseCount('trust_badges', 4);
    $this->assertDatabaseCount('locations', 1);
});

it('fills the home page for development, once', function (): void {
    foreach (['pizza', 'sides', 'drinks'] as $slug) {
        ProductModel::factory()->for(CategoryModel::factory()->create(['slug' => $slug]))->create();
    }

    $this->seed(ContentDevelopmentSeeder::class);
    $this->seed(ContentDevelopmentSeeder::class);

    expect($this->content->visible(ContentType::HeroSlide))->toHaveCount(3)
        ->and($this->content->table(ContentType::Banner))->toHaveCount(3)
        ->and($this->content->visible(ContentType::Banner))->toHaveCount(2) // the third one starts next week
        ->and($this->content->visible(ContentType::GalleryImage))->toHaveCount(5)
        ->and($this->content->homeLayout()->tabCategoryIds)->toHaveCount(2)
        ->and($this->content->homeLayout()->rows)->toHaveCount(2)
        ->and(app(LocationQuery::class)->active())->toHaveCount(2)
        ->and(app(SettingsQuery::class)->current()->openingHoursText)->not->toBe('')
        ->and(Storage::disk('public')->allFiles())->toHaveCount(14); // 3 x 2 slides, 3 banners, 5 photos

    $this->get('/')->assertOk()->assertSee('Hand-stretched pizza')->assertSee('Pasta Tuesday')->assertDontSee('Next week only');
});
