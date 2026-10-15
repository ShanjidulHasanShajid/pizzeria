<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Shared\Application\Ports\FileStorage;
use App\Modules\Shared\Application\Ports\ImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Format;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Gives every product without pictures a generated placeholder (a "plate" on a coloured square,
 * one colour per top-level category) and a 400 px thumbnail. Real photos come from the admin.
 */
final class ProductImageSeeder extends Seeder
{
    /** Seeded pictures live in their own folder on the public disk, apart from real uploads. */
    public const DIRECTORY = 'seed/products';

    /** top-level category slug => background colour */
    private const COLOURS = [
        'pizza' => '#c2410c',
        'pies' => '#b45309',
        'sides' => '#ca8a04',
        'pasta' => '#d97706',
        'burgers-and-sandwiches' => '#92400e',
        'salads' => '#4d7c0f',
        'desserts' => '#be185d',
        'drinks' => '#0e7490',
    ];

    public function __construct(
        private readonly ImageManagerInterface $images,
        private readonly ImageProcessor $processor,
        private readonly FileStorage $files,
        private readonly ProductRepository $products,
    ) {}

    public function run(): void
    {
        // On a fresh database no image row points into the folder, so old files can go.
        if (ProductImageModel::query()->doesntExist()) {
            Storage::disk('public')->deleteDirectory(self::DIRECTORY);
        }

        $rows = ProductModel::query()->with('category.parent')->doesntHave('images')->orderBy('id')->get();

        foreach ($rows as $row) {
            $product = $this->products->find($row->id);

            if ($product === null) {
                continue;
            }

            $topLevelSlug = $row->category->parent->slug ?? $row->category->slug;
            $original = $this->placeholder(self::COLOURS[$topLevelSlug] ?? '#57534e');

            $product->addImage(ImagePath::of(
                $this->files->store(self::DIRECTORY, $original, 'webp'),
                $this->files->store(self::DIRECTORY.'/thumbs', $this->processor->thumbnail($original, 400), 'webp'),
            ));

            $this->products->save($product);
        }
    }

    /**
     * An 800 x 800 WebP: coloured background, a light ring and a coloured centre.
     */
    private function placeholder(string $colour): string
    {
        return $this->images->createImage(800, 800)
            ->fill($colour)
            ->drawCircle(fn (CircleFactory $circle) => $circle->radius(270)->at(400, 400)->background('#fff7ed'))
            ->drawCircle(fn (CircleFactory $circle) => $circle->radius(210)->at(400, 400)->background($colour))
            ->encodeUsingFormat(Format::WEBP, quality: 80)
            ->toString();
    }
}
