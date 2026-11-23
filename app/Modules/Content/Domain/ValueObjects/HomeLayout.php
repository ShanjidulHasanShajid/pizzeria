<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * Which categories the home page shows: as tabs of the product showcase, and as rows of three products.
 */
final readonly class HomeLayout
{
    public const MAX_TABS = 6;

    public const MAX_ROWS = 3;

    /**
     * @param  list<int>  $tabCategoryIds  in order
     * @param  list<HomeRow>  $rows  in order
     */
    public function __construct(
        public array $tabCategoryIds,
        public array $rows,
    ) {
        if (count($tabCategoryIds) > self::MAX_TABS || count(array_unique($tabCategoryIds)) !== count($tabCategoryIds)) {
            throw InvalidValue::because('Choose at most six different categories for the tabs.');
        }

        $rowIds = array_map(fn (HomeRow $row): int => $row->categoryId, $rows);

        if (count($rows) > self::MAX_ROWS || count(array_unique($rowIds)) !== count($rowIds)) {
            throw InvalidValue::because('Choose at most three different categories for the rows.');
        }
    }

    public static function empty(): self
    {
        return new self([], []);
    }
}
