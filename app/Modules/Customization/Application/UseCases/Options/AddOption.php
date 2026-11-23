<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\AddOptionInput;
use App\Modules\Customization\Application\Services\TakaInput;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Adds an available option at the end of its group. A per-size option starts without prices:
 * it is not offered anywhere until the price matrix is filled in.
 */
final class AddOption
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(AddOptionInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);

            $sort = $group->nextOptionSort();
            $option = $input->priceMode === PriceMode::Flat
                ? Option::withFlatPrice($input->name, TakaInput::toMoney($input->flatPriceTaka, 'flat_price'), $sort, $input->isVeg)
                : Option::withPricesPerSize($input->name, [], $sort, $input->isVeg);

            try {
                $group->addOption($option);
            } catch (InvalidOptionGroup $invalid) {
                throw ValidationFailed::forField('name', $invalid->getMessage());
            }

            $this->groups->save($group);
        });
    }
}
