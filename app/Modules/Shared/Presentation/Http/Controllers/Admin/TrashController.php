<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Http\Controllers\Admin;

use App\Modules\Shared\Presentation\Support\TrashItem;
use App\Modules\Shared\Presentation\Support\TrashKind;
use App\Modules\Shared\Presentation\Support\TrashSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The Trash: deleted records of all modules, with one tab per kind. Route requires "access-admin-sections";
 * kinds the user may not manage are left out. Restore and "Delete forever" post to the routes of
 * each kind, which send the admin back here.
 */
final class TrashController
{
    /**
     * @param  array<int, TrashSource>  $sources
     */
    public function __construct(private readonly array $sources) {}

    public function index(Request $request): View
    {
        $kinds = $this->allowedKinds();
        $items = $this->items($kinds);

        // An unknown ?type= (typed by hand) simply shows everything.
        $type = $request->string('type')->toString();
        $type = isset($kinds[$type]) ? $type : 'all';

        return view('shared::admin.trash.index', [
            'tabs' => $this->tabs($kinds, $items),
            'type' => $type,
            'kinds' => $kinds,
            'items' => $type === 'all'
                ? $items
                : array_values(array_filter($items, fn (TrashItem $item): bool => $item->kind === $type)),
        ]);
    }

    /**
     * @return array<string, TrashKind> by key
     */
    private function allowedKinds(): array
    {
        $kinds = [];

        foreach ($this->sources as $source) {
            foreach ($source->kinds() as $kind) {
                if (Gate::allows($kind->ability)) {
                    $kinds[$kind->key] = $kind;
                }
            }
        }

        return $kinds;
    }

    /**
     * Newest deletion first.
     *
     * @param  array<string, TrashKind>  $kinds
     * @return list<TrashItem>
     */
    private function items(array $kinds): array
    {
        $items = [];

        foreach ($this->sources as $source) {
            foreach ($source->items() as $item) {
                if (isset($kinds[$item->kind])) {
                    $items[] = $item;
                }
            }
        }

        usort($items, fn (TrashItem $a, TrashItem $b): int => $b->deletedAt <=> $a->deletedAt);

        return $items;
    }

    /**
     * "All" first, then one tab per kind, each with how many items it holds.
     *
     * @param  array<string, TrashKind>  $kinds
     * @param  list<TrashItem>  $items
     * @return array<string, string> tab key => label, for example "Products (3)"
     */
    private function tabs(array $kinds, array $items): array
    {
        $counts = array_count_values(array_map(fn (TrashItem $item): string => $item->kind, $items));
        $tabs = ['all' => 'All ('.count($items).')'];

        foreach ($kinds as $key => $kind) {
            $tabs[$key] = $kind->tab.' ('.($counts[$key] ?? 0).')';
        }

        return $tabs;
    }
}
