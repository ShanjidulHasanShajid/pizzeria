<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Home;

use App\Modules\Content\Application\DTOs\SaveHomeLayoutInput;
use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Content\Domain\ValueObjects\HomeRow;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Saves which categories the home page shows as tabs and as rows, and the picked products.
 */
final class SaveHomeLayout
{
    public function __construct(
        private readonly HomeLayoutRepository $layouts,
        private readonly HomeCatalog $catalog,
    ) {}

    public function handle(SaveHomeLayoutInput $input): void
    {
        $categories = $this->catalog->categoryNames();

        foreach ($input->tabCategoryIds as $categoryId) {
            if (! isset($categories[$categoryId])) {
                throw ValidationFailed::forField('tabs', 'Choose existing categories only.');
            }
        }

        $rows = [];
        $used = [];

        foreach ($input->rows as $position => $row) {
            if (in_array($row['categoryId'], $used, true)) {
                throw ValidationFailed::forField("rows.{$position}", 'Choose each category for one row only.');
            }

            $used[] = $row['categoryId'];

            if (! isset($categories[$row['categoryId']])) {
                throw ValidationFailed::forField("rows.{$position}", 'Choose an existing category.');
            }

            if (array_diff($row['productIds'], array_keys($this->catalog->productNames($row['categoryId']))) !== []) {
                throw ValidationFailed::forField("rows.{$position}", 'Choose products of this category only.');
            }

            try {
                $rows[] = new HomeRow($row['categoryId'], $row['productIds']);
            } catch (InvalidValue $invalid) {
                throw ValidationFailed::forField("rows.{$position}", $invalid->getMessage());
            }
        }

        try {
            $this->layouts->save(new HomeLayout($input->tabCategoryIds, $rows));
        } catch (InvalidValue $invalid) {
            throw ValidationFailed::forField('tabs', $invalid->getMessage());
        }
    }
}
