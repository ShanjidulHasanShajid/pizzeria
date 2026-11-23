<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Application\Services\ContentImages;
use App\Modules\Content\Application\Services\ContentInputReader;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Content\Domain\ValueObjects\ContentItem;
use Throwable;

/**
 * Adds an item at the end of its list. Returns its id. Everything is checked before a file is stored.
 */
final class CreateContentItem
{
    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly ContentInputReader $reader,
        private readonly ContentImages $images,
    ) {}

    public function handle(SaveContentItemInput $input): int
    {
        $texts = $this->reader->texts($input);
        $uploads = $this->reader->uploads($input);
        $schedule = $this->reader->schedule($input);
        $create = fn (array $paths): ContentItem => ContentItem::create(
            $input->type,
            [...$texts, ...$paths],
            $schedule,
            $input->isActive,
            $this->items->nextSortOrder($input->type),
        );

        $create(array_map(fn (): string => 'pending', $uploads)); // only to check the other fields first

        $paths = $this->images->storeAll($input->type, $uploads);

        try {
            return $this->items->save($create($paths));
        } catch (Throwable $failure) {
            $this->images->deleteAll(array_values($paths));

            throw $failure;
        }
    }
}
