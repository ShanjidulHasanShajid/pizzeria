<?php

declare(strict_types=1);

namespace App\Modules\Customization\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Customization\Application\DTOs\AttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\BulkAttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\SelectionRulesInput;
use App\Modules\Customization\Application\UseCases\Attachments\AttachOptionGroup;
use App\Modules\Customization\Application\UseCases\Attachments\BulkAttachOptionGroup;
use App\Modules\Customization\Infrastructure\Persistence\Models\OptionGroup as OptionGroupModel;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Attaches the sample groups to the sample menu, through the same use cases as the admin screens.
 */
final class ProductOptionGroupSeeder extends Seeder
{
    public function __construct(
        private readonly AdminCatalogQuery $catalog,
        private readonly StorefrontCatalogQuery $shop,
        private readonly BulkAttachOptionGroup $bulkAttach,
        private readonly AttachOptionGroup $attach,
    ) {}

    public function run(): void
    {
        $exactlyOne = new SelectionRulesInput(isRequired: true, minimum: 1, maximum: 1, freeCount: 0);

        $this->toCategory('pizza', 'Crust', $exactlyOne);
        $this->toCategory('pizza', 'Extra Toppings', $this->upTo(5));
        $this->toCategory('pies', 'Cheese', $this->upTo(2));
        $this->toCategory('pies', 'Dips', $this->upTo(3));
        $this->toProduct('sides', 'chicken-wings', 'Wing Flavours', $exactlyOne);
        $this->toCategory('salads', 'Salad Dressings', $exactlyOne);
        $this->toCategory('milkshakes', 'Drink Extras', $this->upTo(3));
        $this->toCategory('coffee', 'Drink Extras', $this->upTo(3));
    }

    private function upTo(int $maximum): SelectionRulesInput
    {
        return new SelectionRulesInput(isRequired: false, minimum: 0, maximum: $maximum, freeCount: 0);
    }

    private function toCategory(string $categorySlug, string $groupName, SelectionRulesInput $rules): void
    {
        $this->bulkAttach->handle(new BulkAttachOptionGroupInput($this->categoryId($categorySlug), $this->groupId($groupName), $rules));
    }

    private function toProduct(string $categorySlug, string $productSlug, string $groupName, SelectionRulesInput $rules): void
    {
        foreach ($this->shop->productCardsForCategory($this->categoryId($categorySlug)) as $card) {
            if ($card->slug === $productSlug) {
                $this->attach->handle(new AttachOptionGroupInput($card->id, $this->groupId($groupName), $rules));

                return;
            }
        }

        throw new RuntimeException("The product \"{$productSlug}\" is missing. Run the catalog seeders first.");
    }

    private function categoryId(string $slug): int
    {
        foreach ($this->catalog->categoryTable() as $row) {
            if ($row->slug === $slug) {
                return $row->id;
            }
        }

        throw new RuntimeException("The category \"{$slug}\" is missing. Run the catalog seeders first.");
    }

    private function groupId(string $name): int
    {
        return OptionGroupModel::query()->where('name', $name)->firstOrFail()->id;
    }
}
