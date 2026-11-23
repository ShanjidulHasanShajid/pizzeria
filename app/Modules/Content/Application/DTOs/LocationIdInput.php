<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

/**
 * Input for actions that need only the location: delete, restore, toggle.
 */
final readonly class LocationIdInput
{
    public function __construct(public int $id) {}
}
