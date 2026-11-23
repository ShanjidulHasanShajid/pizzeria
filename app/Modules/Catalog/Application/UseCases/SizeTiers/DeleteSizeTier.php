<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\SizeTiers;

use App\Modules\Catalog\Application\DTOs\CatalogItemInput;
use App\Modules\Catalog\Application\Ports\SizeTierPrices;
use App\Modules\Catalog\Domain\Exceptions\CatalogItemInUse;
use App\Modules\Catalog\Domain\Repositories\SizeTierRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Moves a size to the Trash. Refused while a variant or an option price uses it
 * (business rules section 3).
 */
final class DeleteSizeTier
{
    public function __construct(
        private readonly SizeTierRepository $sizeTiers,
        private readonly SizeTierPrices $prices,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(CatalogItemInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $sizeTier = $this->sizeTiers->find($input->id)
                ?? throw EntityNotFound::withId('Size tier', $input->id);

            if ($this->sizeTiers->isInUse($input->id)) {
                throw CatalogItemInUse::sizeTier($sizeTier->name());
            }

            if ($this->prices->usesSizeTier($input->id)) {
                throw CatalogItemInUse::sizeTierInOptionPrices($sizeTier->name());
            }

            $this->sizeTiers->delete($input->id);
        });
    }
}
