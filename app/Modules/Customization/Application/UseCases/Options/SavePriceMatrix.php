<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Options;

use App\Modules\Customization\Application\DTOs\SavePriceMatrixInput;
use App\Modules\Customization\Application\Ports\SizeTiers;
use App\Modules\Customization\Application\Services\TakaInput;
use App\Modules\Customization\Domain\Entities\Option;
use App\Modules\Customization\Domain\Entities\OptionGroup;
use App\Modules\Customization\Domain\Enums\PriceMode;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Saves the prices of a whole group at once: options are the rows, size tiers the columns.
 * An empty cell means "not offered at that size", zero means free. Cells that were not sent
 * keep their price. Every mistake is reported under its own cell, and then nothing is saved.
 */
final class SavePriceMatrix
{
    public function __construct(
        private readonly OptionGroupRepository $groups,
        private readonly SizeTiers $sizeTiers,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(SavePriceMatrixInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $group = $this->groups->find($input->optionGroupId)
                ?? throw EntityNotFound::withId('Option group', $input->optionGroupId);

            $errors = [];

            foreach ($input->cells as $optionId => $row) {
                try {
                    $this->applyRow($this->perSizeOption($group, $optionId), $row);
                } catch (ValidationFailed $failure) {
                    $errors = [...$errors, ...$failure->errors()];
                }
            }

            if ($errors !== []) {
                throw ValidationFailed::withErrors($errors);
            }

            $this->groups->save($group);
        });
    }

    private function perSizeOption(OptionGroup $group, int $optionId): Option
    {
        $option = $group->option($optionId)
            ?? throw EntityNotFound::withId('Option', $optionId);

        if ($option->priceMode() !== PriceMode::PerSize) {
            throw ValidationFailed::forField("prices.{$optionId}", "\"{$option->name()}\" has one price for every size. Change that first.");
        }

        return $option;
    }

    /**
     * @param  array<int, ?string>  $row  size tier id => taka as typed
     */
    private function applyRow(Option $option, array $row): void
    {
        $prices = $option->sizePrices();
        $sizeTierIds = $this->sizeTiers->ids();
        $errors = [];

        foreach ($row as $sizeTierId => $taka) {
            $field = "prices.{$option->id()}.{$sizeTierId}";

            if (! in_array($sizeTierId, $sizeTierIds, true)) {
                $errors[$field] = ['Choose an existing size.'];

                continue;
            }

            if (trim($taka ?? '') === '') {
                unset($prices[$sizeTierId]);

                continue;
            }

            try {
                $prices[$sizeTierId] = TakaInput::toMoney($taka, $field);
            } catch (ValidationFailed $failure) {
                $errors = [...$errors, ...$failure->errors()];
            }
        }

        if ($errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        $option->usePricesPerSize($prices);
    }
}
