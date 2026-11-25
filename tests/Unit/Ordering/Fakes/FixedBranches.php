<?php

declare(strict_types=1);

namespace Tests\Unit\Ordering\Fakes;

use App\Modules\Ordering\Application\DTOs\Branch;
use App\Modules\Ordering\Application\Ports\Branches;

final class FixedBranches implements Branches
{
    /** @var list<Branch> */
    private array $branches;

    public function __construct(Branch ...$branches)
    {
        $this->branches = array_values($branches);
    }

    public function all(): array
    {
        return $this->branches;
    }

    public function find(int $id): ?Branch
    {
        foreach ($this->branches as $branch) {
            if ($branch->id === $id) {
                return $branch;
            }
        }

        return null;
    }
}
