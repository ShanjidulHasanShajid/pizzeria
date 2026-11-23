<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\DTOs\TrashedCatalogItem;
use App\Modules\Catalog\Application\Ports\OptionTrash;
use App\Modules\Catalog\Application\Queries\AdminCatalogQuery;
use App\Modules\Catalog\Presentation\Support\TrashKinds;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The Trash: every deleted catalog item and option, with tabs by kind. Route requires "manage-catalog".
 * Restore and "Delete forever" post to the routes of each kind, which send the admin back here.
 */
final class TrashController
{
    public function index(Request $request, AdminCatalogQuery $query, OptionTrash $options): View
    {
        $items = [...$query->trashedItems(), ...$options->items()];
        usort($items, fn (TrashedCatalogItem $a, TrashedCatalogItem $b): int => $b->deletedAt <=> $a->deletedAt);

        // An unknown ?type= (typed by hand) simply shows everything.
        $type = $request->string('type')->toString();
        $type = array_key_exists($type, TrashKinds::KINDS) ? $type : 'all';

        return view('catalog::admin.trash.index', [
            'tabs' => TrashKinds::tabs($items),
            'type' => $type,
            'kinds' => TrashKinds::KINDS,
            'items' => $type === 'all'
                ? $items
                : array_values(array_filter($items, fn (TrashedCatalogItem $item): bool => $item->kind === $type)),
        ]);
    }
}
