<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Where an item is made: Oven, Fryer, Grill ... Kitchen tickets group items by station.
 */
final class KitchenStation
{
    public const NAME_MAX_LENGTH = 100;

    private function __construct(
        private ?int $id,
        private string $name,
        private SortOrder $sort,
        private bool $isActive,
    ) {}

    /**
     * A new station that is not saved yet, so it has no id. New stations start active.
     */
    public static function create(string $name, SortOrder $sort): self
    {
        return new self(null, self::cleanName($name), $sort, true);
    }

    /**
     * Rebuilds a saved station from stored data. Only the mapper (Infrastructure) calls this.
     */
    public static function reconstitute(int $id, string $name, SortOrder $sort, bool $isActive): self
    {
        return new self($id, self::cleanName($name), $sort, $isActive);
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('A kitchen station name must be 1 to 100 characters.');
        }

        return $name;
    }
}
