<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Entities;

use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * One choice inside an option group: "Thin crust", "Olives", "Garlic dip".
 * Part of the OptionGroup aggregate: rename it only through OptionGroup, which keeps names unique.
 * A per-size option without a price for a size is "not offered at that size". A price of zero is free.
 */
final class Option
{
    public const NAME_MAX_LENGTH = 100;

    public const IMAGE_PATH_MAX_LENGTH = 255;

    /**
     * @param  array<int, Money>  $sizePrices  size tier id => price
     */
    private function __construct(
        private ?int $id,
        private string $name,
        private ?string $imagePath,
        private bool $isVeg,
        private bool $isAvailable,
        private SortOrder $sort,
        private PriceMode $priceMode,
        private ?Money $flatPrice,
        private array $sizePrices,
    ) {
        if ($priceMode === PriceMode::Flat && $flatPrice === null) {
            throw InvalidValue::because("Give \"{$name}\" a price, or choose prices per size.");
        }
    }

    /** A new option with one price for every size. It starts available. */
    public static function withFlatPrice(string $name, Money $price, SortOrder $sort, bool $isVeg = false): self
    {
        return new self(null, self::cleanName($name), null, $isVeg, true, $sort, PriceMode::Flat, $price, []);
    }

    /**
     * A new option with a price per size tier. It starts available.
     *
     * @param  array<int, Money>  $prices  size tier id => price
     */
    public static function withPricesPerSize(string $name, array $prices, SortOrder $sort, bool $isVeg = false): self
    {
        return new self(null, self::cleanName($name), null, $isVeg, true, $sort, PriceMode::PerSize, null, self::validSizePrices($prices));
    }

    /**
     * Rebuilds a saved option. Only the mapper (Infrastructure) and tests call this.
     *
     * @param  array<int, Money>  $sizePrices  size tier id => price
     */
    public static function reconstitute(
        int $id,
        string $name,
        ?string $imagePath,
        bool $isVeg,
        bool $isAvailable,
        SortOrder $sort,
        PriceMode $priceMode,
        ?Money $flatPrice,
        array $sizePrices,
    ): self {
        return new self(
            $id,
            self::cleanName($name),
            self::cleanImagePath($imagePath),
            $isVeg,
            $isAvailable,
            $sort,
            $priceMode,
            $flatPrice,
            self::validSizePrices($sizePrices),
        );
    }

    /**
     * The price for a variant of this size tier, or null when the option is not offered at that size.
     * A per-size option on a variant without a size tier ("6 pcs") is not offered (business rules section 4).
     */
    public function priceFor(?int $sizeTierId): ?Money
    {
        if ($this->priceMode === PriceMode::Flat) {
            return $this->flatPrice;
        }

        if ($sizeTierId === null) {
            return null;
        }

        return $this->sizePrices[$sizeTierId] ?? null;
    }

    /** Switches to one price for every size. The per-size prices are kept for a switch back. */
    public function useFlatPrice(Money $price): void
    {
        $this->priceMode = PriceMode::Flat;
        $this->flatPrice = $price;
    }

    /**
     * Switches to prices per size and replaces them all. The flat price is kept for a switch back.
     *
     * @param  array<int, Money>  $prices  size tier id => price
     */
    public function usePricesPerSize(array $prices): void
    {
        $this->priceMode = PriceMode::PerSize;
        $this->sizePrices = self::validSizePrices($prices);
    }

    /** Only OptionGroup calls this, because names must stay unique inside the group. */
    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function changeImage(?string $imagePath): void
    {
        $this->imagePath = self::cleanImagePath($imagePath);
    }

    public function markVeg(): void
    {
        $this->isVeg = true;
    }

    public function markNotVeg(): void
    {
        $this->isVeg = false;
    }

    public function markAvailable(): void
    {
        $this->isAvailable = true;
    }

    public function markSoldOut(): void
    {
        $this->isAvailable = false;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    /** Names are compared without case and outer spaces: "Thin" and " thin" are the same name. */
    public function hasName(string $name): bool
    {
        return mb_strtolower($this->name) === mb_strtolower(trim($name));
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function imagePath(): ?string
    {
        return $this->imagePath;
    }

    public function isVeg(): bool
    {
        return $this->isVeg;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    public function priceMode(): PriceMode
    {
        return $this->priceMode;
    }

    public function flatPrice(): ?Money
    {
        return $this->flatPrice;
    }

    /** @return array<int, Money> size tier id => price, by size tier id */
    public function sizePrices(): array
    {
        return $this->sizePrices;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('An option name must be 1 to 100 characters.');
        }

        return $name;
    }

    private static function cleanImagePath(?string $imagePath): ?string
    {
        $imagePath = $imagePath === null ? '' : trim($imagePath);

        if ($imagePath === '') {
            return null;
        }

        if (mb_strlen($imagePath) > self::IMAGE_PATH_MAX_LENGTH) {
            throw InvalidValue::because('The image path is too long.');
        }

        return $imagePath;
    }

    /**
     * @param  array<int, Money>  $prices
     * @return array<int, Money>
     */
    private static function validSizePrices(array $prices): array
    {
        foreach (array_keys($prices) as $sizeTierId) {
            if ($sizeTierId < 1) {
                throw InvalidValue::because('Choose a valid size for every price.');
            }
        }

        ksort($prices);

        return $prices;
    }
}
