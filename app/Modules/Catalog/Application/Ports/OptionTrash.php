<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Ports;

use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;

/**
 * The deleted option groups and options of the Customization module, for the Trash screen.
 */
interface OptionTrash
{
    /**
     * @return list<TrashedCatalogItem>
     */
    public function items(): array;
}
