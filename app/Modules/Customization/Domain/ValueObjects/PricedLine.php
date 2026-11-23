<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\ValueObjects;

use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

/**
 * The result of pricing one line: base price, every chosen option with what it costs,
 * the unit price (base + options) and the line total (unit price x quantity).
 * fromParts() does the sums, so the numbers can never disagree with each other.
 */
final readonly class PricedLine
{
    /**
     * @param  list<PricedOption>  $options
     */
    private function __construct(
        private int $productId,
        private string $productName,
        private int $variantId,
        private string $variantLabel,
        private Money $basePrice,
        private array $options,
        private Quantity $quantity,
        private Money $unitPrice,
        private Money $lineTotal,
    ) {}

    /**
     * @param  list<PricedOption>  $options
     */
    public static function fromParts(
        int $productId,
        string $productName,
        int $variantId,
        string $variantLabel,
        Money $basePrice,
        array $options,
        Quantity $quantity,
    ): self {
        $unitPrice = $basePrice;

        foreach ($options as $option) {
            $unitPrice = $unitPrice->add($option->charged());
        }

        return new self(
            $productId,
            $productName,
            $variantId,
            $variantLabel,
            $basePrice,
            $options,
            $quantity,
            $unitPrice,
            $unitPrice->multiply($quantity),
        );
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function variantId(): int
    {
        return $this->variantId;
    }

    public function variantLabel(): string
    {
        return $this->variantLabel;
    }

    public function basePrice(): Money
    {
        return $this->basePrice;
    }

    /**
     * @return list<PricedOption> group by group in the product's order, options in the group's order
     */
    public function options(): array
    {
        return $this->options;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function lineTotal(): Money
    {
        return $this->lineTotal;
    }

    /**
     * A readable explanation, one line per part, for example:
     * "Margherita (Large): ৳1250.00", "Crust: Stuffed +৳200.00", "Extra toppings: Olives free",
     * "Each: ৳1450.00", "Total: 2 × ৳1450.00 = ৳2900.00".
     *
     * @return list<string>
     */
    public function breakdown(): array
    {
        $lines = ["{$this->productName} ({$this->variantLabel}): ".self::taka($this->basePrice)];

        foreach ($this->options as $option) {
            $lines[] = $option->groupTitle().': '.$option->optionName().' '
                .($option->charged()->isZero() ? 'free' : '+'.self::taka($option->charged()));
        }

        $lines[] = 'Each: '.self::taka($this->unitPrice);
        $lines[] = sprintf('Total: %d × %s = %s', $this->quantity->value(), self::taka($this->unitPrice), self::taka($this->lineTotal));

        return $lines;
    }

    private static function taka(Money $money): string
    {
        return '৳'.$money->toTakaString();
    }
}
