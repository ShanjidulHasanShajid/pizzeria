<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\SaveHomeLayoutInput;
use App\Modules\Content\Application\Ports\HomeCatalog;
use App\Modules\Content\Application\UseCases\Home\SaveHomeLayout;
use App\Modules\Content\Domain\Repositories\HomeLayoutRepository;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/*
| Categories 1 to 8 exist. Category 1 has the products 11, 12, 13 and 14; the others have none.
*/
beforeEach(function (): void {
    $catalog = new class implements HomeCatalog
    {
        public function categoryNames(): array
        {
            return array_fill_keys(range(1, 8), 'Category');
        }

        public function productNames(int $categoryId): array
        {
            return $categoryId === 1 ? array_fill_keys([11, 12, 13, 14], 'Product') : [];
        }
    };
    $this->layouts = new class implements HomeLayoutRepository
    {
        public ?HomeLayout $saved = null;

        public function get(): HomeLayout
        {
            return $this->saved ?? HomeLayout::empty();
        }

        public function save(HomeLayout $layout): void
        {
            $this->saved = $layout;
        }
    };
    $this->save = fn (array $tabs, array $rows = []) => (new SaveHomeLayout($this->layouts, $catalog))->handle(new SaveHomeLayoutInput($tabs, $rows));
});

it('saves tabs and rows in their order, with picked products', function (): void {
    ($this->save)([3, 1], [['categoryId' => 1, 'productIds' => [13, 11]], ['categoryId' => 2, 'productIds' => []]]);

    $layout = $this->layouts->get();

    expect($layout->tabCategoryIds)->toBe([3, 1])
        ->and($layout->rows[0]->categoryId)->toBe(1)
        ->and($layout->rows[0]->productIds)->toBe([13, 11])
        ->and($layout->rows[1]->productIds)->toBe([]);
});

it('can be emptied', function (): void {
    ($this->save)([1]);
    ($this->save)([]);

    expect($this->layouts->get()->tabCategoryIds)->toBe([]);
});

it('refuses choices that do not work', function (array $tabs, array $rows, string $field): void {
    try {
        ($this->save)($tabs, $rows);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe([$field])
            ->and($this->layouts->saved)->toBeNull();

        return;
    }

    throw new RuntimeException('This layout must be refused.');
})->with([
    'unknown tab category' => [[99], [], 'tabs'],
    'the same tab twice' => [[1, 1], [], 'tabs'],
    'seven tabs' => [[1, 2, 3, 4, 5, 6, 7], [], 'tabs'],
    'unknown row category' => [[], [['categoryId' => 99, 'productIds' => []]], 'rows.0'],
    'the same row twice' => [[], [['categoryId' => 2, 'productIds' => []], ['categoryId' => 2, 'productIds' => []]], 'rows.1'],
    'a product of another category' => [[], [1 => ['categoryId' => 2, 'productIds' => [11]]], 'rows.1'],
    'a product twice' => [[], [['categoryId' => 1, 'productIds' => [11, 11]]], 'rows.0'],
    'four products' => [[], [['categoryId' => 1, 'productIds' => [11, 12, 13, 14]]], 'rows.0'],
]);
