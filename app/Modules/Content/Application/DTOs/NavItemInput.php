<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\NavRoute;

/**
 * Input for actions that need only one navbar item: toggle visible, toggle highlight.
 */
final readonly class NavItemInput
{
    public function __construct(public NavRoute $route) {}
}
