<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Items;

use App\Modules\Content\Application\DTOs\SaveContentItemInput;
use App\Modules\Content\Application\Services\ContentImages;
use App\Modules\Content\Application\Services\ContentInputReader;
use App\Modules\Content\Domain\Repositories\ContentItemRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use Throwable;

/**
 * Saves the edit form. New files are stored first, then the row, and only then are replaced
 * or removed files deleted. A failed save deletes the new files and keeps the old ones.
 */
final class UpdateContentItem
{
    public function __construct(
        private readonly ContentItemRepository $items,
        private readonly ContentInputReader $reader,
        private readonly ContentImages $images,
    ) {}

    public function handle(SaveContentItemInput $input): void
    {
        $id = (int) $input->id;
        $item = $this->items->find($input->type, $id)
            ?? throw EntityNotFound::withId($input->type->singular(), $id);

        $values = $this->reader->texts($input);
        $uploads = $this->reader->uploads($input);
        $schedule = $this->reader->schedule($input);

        foreach ($input->type->fields() as $field) {
            if ($field->isImage() && ! isset($uploads[$field->name]) && in_array($field->name, $input->removedImages, true)) {
                $values[$field->name] = '';
            }
        }

        $pending = array_map(fn (): string => 'pending', $uploads);
        $item->change([...$values, ...$pending], $schedule, $input->isActive); // only to check first

        $paths = $this->images->storeAll($input->type, $uploads);

        try {
            $changed = $item->change([...$values, ...$paths], $schedule, $input->isActive);
            $this->items->save($changed);
        } catch (Throwable $failure) {
            $this->images->deleteAll(array_values($paths));

            throw $failure;
        }

        $this->images->deleteAll(array_values(array_diff($item->imagePaths(), $changed->imagePaths())));
    }
}
