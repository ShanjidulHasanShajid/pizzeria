<?php

declare(strict_types=1);

namespace App\Modules\Cart\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\InvalidValue;

/**
 * The chosen option ids per option group. Only ids, never prices. Everything is sorted,
 * so the order of the clicks never makes two equal choices look different.
 */
final readonly class ChosenOptions
{
    /**
     * @param  array<int, list<int>>  $choices  option group id => chosen option ids
     */
    private function __construct(private array $choices) {}

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param  array<int, array<int, int>>  $choices  option group id => chosen option ids
     */
    public static function of(array $choices): self
    {
        $clean = [];

        foreach ($choices as $optionGroupId => $optionIds) {
            if ($optionGroupId < 1 || array_filter($optionIds, fn (int $id): bool => $id < 1) !== []) {
                throw InvalidValue::because('Choose valid options.');
            }

            if (count(array_unique($optionIds)) !== count($optionIds)) {
                throw InvalidValue::because('The same option was chosen twice.');
            }

            if ($optionIds === []) {
                continue; // an empty group is the same as no picks
            }

            sort($optionIds);
            $clean[$optionGroupId] = $optionIds;
        }

        ksort($clean);

        return new self($clean);
    }

    /**
     * @return array<int, list<int>> option group id => chosen option ids
     */
    public function toArray(): array
    {
        return $this->choices;
    }

    public function equals(self $other): bool
    {
        return $this->choices === $other->choices;
    }
}
