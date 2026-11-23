<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\NavRoute;
use App\Modules\Content\Domain\ValueObjects\NavItem;

final readonly class NavItemView
{
    public function __construct(
        public NavRoute $route,
        public string $label,
        public bool $isVisible,
        public bool $isHighlighted,
    ) {}

    public static function fromItem(NavItem $item): self
    {
        return new self($item->route, $item->label, $item->isVisible, $item->isHighlighted);
    }
}
