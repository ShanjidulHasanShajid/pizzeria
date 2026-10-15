<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Seeders;

use App\Modules\Catalog\Domain\Entities\SizeTier;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Seeder;

/**
 * The shared sizes of pizzas and pies (decision 9).
 */
final class SizeTierSeeder extends Seeder
{
    /** slug => name */
    private const TIERS = ['personal' => 'Personal', 'medium' => 'Medium', 'large' => 'Large'];

    public function __construct(private readonly SizeTierRepository $sizeTiers) {}

    public function run(): void
    {
        $sort = SortOrder::first();

        foreach (self::TIERS as $slug => $name) {
            $this->sizeTiers->save(SizeTier::create($name, Slug::fromString($slug), $sort));
            $sort = $sort->next();
        }
    }
}
