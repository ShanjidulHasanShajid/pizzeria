<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

/**
 * What the Trash screen needs to know about one kind of deleted record.
 */
final readonly class TrashKind
{
    /**
     * @param  string  $key  unique across all modules; also the ?type= of its tab
     * @param  string  $route  start of its route names; ".restore" and ".force-destroy" are added
     * @param  string  $forever  what "Delete forever" also removes
     * @param  string  $ability  the gate that lets a user manage this kind
     */
    public function __construct(
        public string $key,
        public string $tab,
        public string $one,
        public string $route,
        public string $forever,
        public string $ability,
    ) {}
}
