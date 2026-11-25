<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Adapters;

use App\Modules\Content\Application\DTOs\LocationView;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Ordering\Application\DTOs\Branch;
use App\Modules\Ordering\Application\Ports\Branches;

final class ContentBranches implements Branches
{
    public function __construct(private readonly LocationQuery $locations) {}

    public function all(): array
    {
        return array_map($this->toBranch(...), $this->locations->table());
    }

    public function find(int $id): ?Branch
    {
        $location = $this->locations->location($id);

        return $location === null ? null : $this->toBranch($location);
    }

    private function toBranch(LocationView $location): Branch
    {
        return new Branch($location->id, $location->name, $location->isActive);
    }
}
