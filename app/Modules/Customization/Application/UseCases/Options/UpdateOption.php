<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\UpdateOptionInput;
use App\Modules\Customization\Application\Services\TakaInput;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Exceptions\InvalidOptionGroup;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Changes the name, the veg mark and how an option is priced. Switching the price mode
 * keeps the other prices, so switching back loses nothing.
 */
final class UpdateOption
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(UpdateOptionInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);
            $option = $group->option($input->optionId)
                ?? throw EntityNotFound::withId('Option', $input->optionId);

            try {
                $group->renameOption($input->optionId, $input->name);
            } catch (InvalidOptionGroup $invalid) {
                throw ValidationFailed::forField('name', $invalid->getMessage());
            }

            if ($input->isVeg) {
                $option->markVeg();
            } else {
                $option->markNotVeg();
            }

            if ($input->priceMode === PriceMode::Flat) {
                $option->useFlatPrice(TakaInput::toMoney($input->flatPriceTaka, 'flat_price'));
            } else {
                $option->usePricesPerSize($option->sizePrices());
            }

            $this->groups->save($group);
        });
    }
}
