<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * Input for actions that need only the id of one option group: delete, restore.
 */
final readonly class OptionGroupItemInput
{
    public function __construct(public int $id) {}
}
