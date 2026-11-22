<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

/**
 * What the admin category list should show.
 */
final readonly class AdminCategoryFilter
{
    /**
     * @param  string  $search  part of the name or slug; empty = no search
     * @param  string  $status  all, active, inactive or trashed
     */
    public function __construct(
        public string $search = '',
        public string $status = 'all',
    ) {}

    /**
     * Without search and status the list is a tree (parents with their children) that can be reordered.
     * With them it is a flat list of whatever matches.
     */
    public function isTree(): bool
    {
        return $this->search === '' && $this->status === 'all';
    }
}
