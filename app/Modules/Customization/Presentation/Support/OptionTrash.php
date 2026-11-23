<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Support;

use App\Modules\Customization\Application\DTOs\TrashedOptionItem;
use App\Modules\Customization\Application\Queries\TrashedOptionsQuery;
use App\Modules\Shared\Presentation\Support\TrashItem;
use App\Modules\Shared\Presentation\Support\TrashKind;
use App\Modules\Shared\Presentation\Support\TrashSource;

/**
 * What the Customization module adds to the Trash.
 */
final class OptionTrash implements TrashSource
{
    public function __construct(private readonly TrashedOptionsQuery $options) {}

    public function kinds(): array
    {
        return [
            new TrashKind('option_group', 'Option groups', 'Option group', 'admin.option-groups', 'The group, its options, their prices and images are deleted for good. This only works when no product has the group, also none in the Trash.', 'manage-catalog'),
            new TrashKind('option', 'Options', 'Option', 'admin.options', 'The option, its prices and its image are deleted for good. Old orders keep their own copy of the name and price.', 'manage-catalog'),
        ];
    }

    public function items(): array
    {
        return array_map(
            fn (TrashedOptionItem $item): TrashItem => new TrashItem($item->isGroup ? 'option_group' : 'option', $item->id, $item->name, $item->deletedAt),
            $this->options->items(),
        );
    }
}
