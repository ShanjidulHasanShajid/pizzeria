<?php

declare(strict_types=1);

namespace App\Modules\Customization\Domain\Entities;

use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * A reusable set of choices: "Crust", "Extra toppings", "Dips", "Wing flavours".
 * It is the aggregate root of its options, so the rule "option names are unique in a group"
 * is checked here. The same group can be attached to many products (ProductOptionGroup).
 */
final class OptionGroup extends AggregateRoot
{
    public const NAME_MAX_LENGTH = 100;

    public const TITLE_MAX_LENGTH = 100;

    public const DESCRIPTION_MAX_LENGTH = 500;

    /**
     * @param  list<Option>  $options
     */
    private function __construct(
        private ?int $id,
        private string $name,
        private string $displayTitle,
        private ?string $description,
        private bool $isActive,
        private SortOrder $sort,
        private array $options,
    ) {}

    /**
     * A new, active group without options. The name is for the admin ("Pizza crusts"),
     * the display title is what customers read ("Choose your crust").
     */
    public static function create(string $name, string $displayTitle, SortOrder $sort, ?string $description = null): self
    {
        return new self(
            null,
            self::cleanName($name),
            self::cleanTitle($displayTitle),
            self::cleanDescription($description),
            true,
            $sort,
            [],
        );
    }

    /**
     * Rebuilds a saved group with its options. Only the mapper (Infrastructure) and tests call this.
     *
     * @param  list<Option>  $options
     */
    public static function reconstitute(
        int $id,
        string $name,
        string $displayTitle,
        ?string $description,
        bool $isActive,
        SortOrder $sort,
        array $options,
    ): self {
        self::guardUniqueNames($options);

        return new self(
            $id,
            self::cleanName($name),
            self::cleanTitle($displayTitle),
            self::cleanDescription($description),
            $isActive,
            $sort,
            $options,
        );
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function retitle(string $displayTitle): void
    {
        $this->displayTitle = self::cleanTitle($displayTitle);
    }

    public function describe(?string $description): void
    {
        $this->description = self::cleanDescription($description);
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function addOption(Option $option): void
    {
        if ($this->findByName($option->name()) !== null) {
            throw InvalidOptionGroup::duplicateOptionName($option->name());
        }

        $this->options[] = $option;
    }

    public function renameOption(int $optionId, string $newName): void
    {
        $option = $this->option($optionId) ?? throw InvalidOptionGroup::unknownOption($optionId);
        $sameName = $this->findByName($newName);

        if ($sameName !== null && $sameName !== $option) {
            throw InvalidOptionGroup::duplicateOptionName(trim($newName));
        }

        $option->rename($newName);
    }

    /** The saved option with this id, or null. */
    public function option(int $optionId): ?Option
    {
        foreach ($this->options as $option) {
            if ($option->id() === $optionId) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return list<Option> in sort order
     */
    public function options(): array
    {
        $options = $this->options;
        usort($options, fn (Option $a, Option $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $options;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayTitle(): string
    {
        return $this->displayTitle;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    private function findByName(string $name): ?Option
    {
        foreach ($this->options as $option) {
            if ($option->hasName($name)) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param  list<Option>  $options
     */
    private static function guardUniqueNames(array $options): void
    {
        $seen = [];

        foreach ($options as $option) {
            $key = mb_strtolower($option->name());

            if (isset($seen[$key])) {
                throw InvalidOptionGroup::duplicateOptionName($option->name());
            }

            $seen[$key] = true;
        }
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('A group name must be 1 to 100 characters.');
        }

        return $name;
    }

    private static function cleanTitle(string $displayTitle): string
    {
        $displayTitle = trim($displayTitle);

        if ($displayTitle === '' || mb_strlen($displayTitle) > self::TITLE_MAX_LENGTH) {
            throw InvalidValue::because('A display title must be 1 to 100 characters.');
        }

        return $displayTitle;
    }

    private static function cleanDescription(?string $description): ?string
    {
        $description = $description === null ? '' : trim($description);

        if ($description === '') {
            return null;
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            throw InvalidValue::because('A description can have at most 500 characters.');
        }

        return $description;
    }
}
