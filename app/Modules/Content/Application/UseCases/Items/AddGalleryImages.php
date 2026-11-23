<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\AddGalleryImagesInput;
use App\Modules\Content\Application\DTOs\ImageUpload;
use App\Modules\Content\Application\Services\ContentImages;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use Throwable;

/**
 * Adds several photos to the gallery at once, without captions. All of them or none.
 */
final class AddGalleryImages
{
    public const MAX_IMAGES = 5;

    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly ContentImages $images,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(AddGalleryImagesInput $input): void
    {
        $uploads = [];

        foreach ($input->images as $number => $image) {
            $uploads["image-{$number}"] = $image;
        }

        /** @var array<string, ImageUpload> $uploads */
        $paths = array_values($this->images->storeAll(ContentType::GalleryImage, $uploads));

        try {
            $this->transactions->run(function () use ($paths): void {
                $sort = $this->items->nextSortOrder(ContentType::GalleryImage);

                foreach ($paths as $path) {
                    $this->items->save(ContentItem::create(ContentType::GalleryImage, ['image' => $path], DateRange::always(), true, $sort));
                    $sort = $sort->next();
                }
            });
        } catch (Throwable $failure) {
            $this->images->deleteAll($paths);

            throw $failure;
        }
    }
}
