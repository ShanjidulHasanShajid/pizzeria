<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Content\Application\DTOs\AddGalleryImagesInput;
use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Application\DTOs\SaveHomeLayoutInput;
use App\Modules\Content\Application\DTOs\SaveLocationInput;
use App\Modules\Content\Application\UseCases\Home\SaveHomeLayout;
use App\Modules\Content\Application\UseCases\Items\AddGalleryImages;
use App\Modules\Content\Application\UseCases\Items\CreateContentItem;
use App\Modules\Content\Application\UseCases\Locations\CreateLocation;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Enums\Weekday;
use App\Modules\Content\Domain\Repositories\SettingsRepository;
use App\Modules\Content\Infrastructure\Persistence\Models\HeroSlide;
use App\Modules\Content\Infrastructure\Persistence\Models\HomeSection;
use App\Modules\Content\Infrastructure\Persistence\Models\Setting;
use App\Modules\Shared\Application\Ports\Clock;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Format;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Sample home page: hero slides, banners, gallery, branches, home layout and a few settings.
 * Development only, after the catalog. Runs once: if hero slides exist, nothing happens, so
 * content you made yourself is never mixed with samples.
 */
final class ContentDevelopmentSeeder extends Seeder
{
    private const GALLERY_COLOURS = ['#c2410c', '#4d7c0f', '#b45309', '#0e7490', '#be185d'];

    public function __construct(
        private readonly CreateContentItem $createItem,
        private readonly AddGalleryImages $addGalleryImages,
        private readonly CreateLocation $createLocation,
        private readonly SaveHomeLayout $saveHomeLayout,
        private readonly SettingsRepository $settings,
        private readonly AdminCatalogQuery $catalog,
        private readonly ImageManagerInterface $images,
        private readonly Clock $clock,
    ) {}

    public function run(): void
    {
        if (HeroSlide::withTrashed()->exists()) {
            return;
        }

        // All or nothing, so it can run again after a failure.
        DB::transaction(function (): void {
            $this->heroSlides();
            $this->banners();
            $this->addGalleryImages->handle(new AddGalleryImagesInput(array_map(
                fn (string $colour): ImageUpload => $this->picture(900, 900, $colour),
                self::GALLERY_COLOURS,
            )));
            $this->branches();
            $this->homeLayout();
            $this->someSettings();
        });
    }

    private function heroSlides(): void
    {
        $slides = [
            ['#7c2d12', 'Hand-stretched pizza, delivered hot', 'Fresh from the oven to your door.', 'Order now', '/menu'],
            ['#14532d', 'Your pizza, your toppings', 'Pick the size, crust and toppings.', 'Start building', '/build-your-pizza'],
            ['#78350f', 'Feed everyone for less', 'Combos for two and for the family.', 'See deals', '/meal-deals'],
        ];

        foreach ($slides as [$colour, $title, $subtitle, $button, $link]) {
            $this->item(
                ContentType::HeroSlide,
                ['title' => $title, 'subtitle' => $subtitle, 'button_text' => $button, 'button_link' => $link],
                ['image' => $this->picture(1600, 800, $colour), 'mobile_image' => $this->picture(800, 1000, $colour)],
            );
        }
    }

    private function banners(): void
    {
        $now = $this->clock->now();

        $this->item(ContentType::Banner, ['title' => 'Garlic bread with every large pizza', 'link' => '/menu'], ['image' => $this->picture(1200, 600, '#b45309')]);
        $this->item(ContentType::Banner, ['title' => 'Pasta Tuesday', 'link' => '/menu/pasta'], ['image' => $this->picture(1200, 600, '#166534')]);
        $this->item(ContentType::Banner, ['title' => 'Next week only', 'link' => '/menu'], ['image' => $this->picture(1200, 600, '#1e3a8a')], $now->modify('+7 days'), $now->modify('+14 days'));
    }

    private function branches(): void
    {
        $week = array_fill_keys(array_map(fn (Weekday $day): string => $day->value, Weekday::cases()), ['11:00', '23:00']);
        $week['friday'] = ['15:00', '00:30'];

        foreach ([['Gulshan (sample)', 'House 12, Road 45', 'Gulshan 2', '01700 000001'], ['Dhanmondi (sample)', 'House 8, Road 27', 'Dhanmondi', '01700 000002']] as [$name, $address, $area, $phone]) {
            $this->createLocation->handle(new SaveLocationInput(null, $name, $address, $area, $phone, '', '', '', $week, true, true));
        }
    }

    private function homeLayout(): void
    {
        if (HomeSection::query()->exists()) {
            return;
        }

        $ids = [];

        foreach ($this->catalog->categoryTable() as $category) {
            $ids[$category->slug] = $category->id;
        }

        $pick = fn (string ...$slugs): array => array_values(array_intersect_key($ids, array_flip($slugs)));

        $this->saveHomeLayout->handle(new SaveHomeLayoutInput(
            $pick('pizza', 'sides', 'pasta', 'desserts'),
            array_map(fn (int $id): array => ['categoryId' => $id, 'productIds' => []], $pick('pizza', 'burgers-and-sandwiches', 'drinks')),
        ));
    }

    private function someSettings(): void
    {
        if (Setting::query()->exists()) {
            return;
        }

        $this->settings->save([
            'phone' => '01700 000000',
            'delivery_notice' => 'Delivering across Gulshan, Banani, Dhanmondi and more',
            'opening_hours_text' => 'Every day from 11:00 AM, on Friday from 3:00 PM',
        ]);
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, ImageUpload>  $uploads
     */
    private function item(ContentType $type, array $values, array $uploads, ?DateTimeImmutable $startsAt = null, ?DateTimeImmutable $endsAt = null): void
    {
        $this->createItem->handle(new SaveContentItemInput($type, null, $values, $uploads, [], $startsAt, $endsAt, true));
    }

    /** A coloured picture with a "plate". Real photos come from the admin. */
    private function picture(int $width, int $height, string $colour): ImageUpload
    {
        $radius = intdiv(min($width, $height), 3);

        return new ImageUpload($this->images->createImage($width, $height)
            ->fill($colour)
            ->drawCircle(fn (CircleFactory $circle) => $circle->radius($radius)->at(intdiv($width * 2, 3), intdiv($height, 2))->background('#fed7aa'))
            ->encodeUsingFormat(Format::PNG)
            ->toString());
    }
}
