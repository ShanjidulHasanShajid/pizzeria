<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * The quick on/off switch on the size list. Returns the new state (true = active).
 */
final class ToggleSizeTierActive
{
    public function __construct(private readonly SizeTierRepository $sizeTiers) {}

    public function handle(CatalogItemInput $input): bool
    {
        $sizeTier = $this->sizeTiers->find($input->id)
            ?? throw EntityNotFound::withId('Size tier', $input->id);

        if ($sizeTier->isActive()) {
            $sizeTier->deactivate();
        } else {
            $sizeTier->activate();
        }

        $this->sizeTiers->save($sizeTier);

        return $sizeTier->isActive();
    }
}
