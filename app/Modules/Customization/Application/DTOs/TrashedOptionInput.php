<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\DTOs;

/**
 * Input for actions on one option that is in the Trash: restore, delete forever.
 */
final readonly class TrashedOptionInput
{
    public function __construct(public int $optionId) {}
}
