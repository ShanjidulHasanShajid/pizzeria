<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Attachments;

use App\Modules\Customization\Application\DTOs\BulkAttachOptionGroupInput;
use App\Modules\Customization\Application\DTOs\BulkAttachResult;
use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Services\OptionGroupAttacher;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Attaches a group to every product of a category and its sub-categories, all or nothing.
 * Products that already have the group keep their own rules.
 */
final class BulkAttachOptionGroup
{
    public function __construct(
        private readonly CustomizableProducts $products,
        private readonly OptionGroupRepository $groups,
        private readonly OptionGroupAttacher $attacher,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(BulkAttachOptionGroupInput $input): BulkAttachResult
    {
        $rules = $input->rules->toRules();

        return $this->transactions->run(function () use ($input, $rules): BulkAttachResult {
            if ($this->groups->find($input->optionGroupId) === null) {
                throw ValidationFailed::forField('option_group_id', 'Choose an existing option group.');
            }

            $productIds = $this->products->idsInCategory($input->categoryId)
                ?? throw ValidationFailed::forField('category_id', 'Choose an existing category.');

            $attached = 0;

            foreach ($productIds as $productId) {
                if ($this->attacher->attach($productId, $input->optionGroupId, $rules) !== null) {
                    $attached++;
                }
            }

            return new BulkAttachResult($attached, count($productIds) - $attached);
        });
    }
}
