<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Entities;

use App\Modules\Customization\Domain\ValueObjects\SelectionRules;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * One option group attached to one product, with the rules for that product:
 * "Crust" is required (exactly 1) on pizzas; "Dips" is optional (up to 3, 1 free) on wings.
 * The product (Catalog module) and the group are other aggregates, so only their ids are kept.
 */
final class ProductOptionGroup
{
    private function __construct(
        private ?int $id,
        private int $productId,
        private int $optionGroupId,
        private SelectionRules $rules,
        private SortOrder $sort,
    ) {}

    public static function attach(int $productId, int $optionGroupId, SelectionRules $rules, SortOrder $sort): self
    {
        return new self(null, self::validId($productId, 'product'), self::validId($optionGroupId, 'option group'), $rules, $sort);
    }

    public static function reconstitute(int $id, int $productId, int $optionGroupId, SelectionRules $rules, SortOrder $sort): self
    {
        return new self($id, self::validId($productId, 'product'), self::validId($optionGroupId, 'option group'), $rules, $sort);
    }

    public function changeRules(SelectionRules $rules): void
    {
        $this->rules = $rules;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function optionGroupId(): int
    {
        return $this->optionGroupId;
    }

    public function rules(): SelectionRules
    {
        return $this->rules;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    private static function validId(int $id, string $what): int
    {
        if ($id < 1) {
            throw InvalidValue::because("Choose a valid {$what}.");
        }

        return $id;
    }
}
