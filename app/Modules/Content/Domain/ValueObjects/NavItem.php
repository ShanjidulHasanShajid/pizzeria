<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * One entry of the navbar. There is exactly one for each whitelisted page.
 */
final readonly class NavItem
{
    public const LABEL_MAX_LENGTH = 30;

    private function __construct(
        public NavRoute $route,
        public string $label,
        public SortOrder $sort,
        public bool $isVisible,
        public bool $isHighlighted,
    ) {}

    /** What an item is worth until the admin changes it for the first time. */
    public static function default(NavRoute $route): self
    {
        $position = (int) array_search($route, NavRoute::cases(), true);

        return new self(
            $route,
            $route->defaultLabel(),
            SortOrder::fromInt(($position + 1) * 10),
            $route->isVisibleByDefault(),
            $route->isHighlightedByDefault(),
        );
    }

    /** Rebuilds a saved item. Only the repository and tests call this. */
    public static function reconstitute(NavRoute $route, string $label, SortOrder $sort, bool $isVisible, bool $isHighlighted): self
    {
        return new self($route, $label, $sort, $isVisible, $isHighlighted);
    }

    public function rename(string $label): self
    {
        $label = trim($label);

        if ($label === '') {
            throw ValidationFailed::forField('label', 'Enter a label.');
        }

        if (mb_strlen($label) > self::LABEL_MAX_LENGTH) {
            throw ValidationFailed::forField('label', 'Use at most '.self::LABEL_MAX_LENGTH.' characters.');
        }

        return new self($this->route, $label, $this->sort, $this->isVisible, $this->isHighlighted);
    }

    public function withVisibilityToggled(): self
    {
        return new self($this->route, $this->label, $this->sort, ! $this->isVisible, $this->isHighlighted);
    }

    public function withHighlightToggled(): self
    {
        return new self($this->route, $this->label, $this->sort, $this->isVisible, ! $this->isHighlighted);
    }

    public function movedTo(SortOrder $sort): self
    {
        return new self($this->route, $this->label, $sort, $this->isVisible, $this->isHighlighted);
    }
}
