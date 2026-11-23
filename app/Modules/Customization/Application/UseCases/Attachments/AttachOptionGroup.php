<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Attachments;

use App\Modules\Customization\Application\DTOs\AttachOptionGroupInput;
use App\Modules\Customization\Application\Ports\CustomizableProducts;
use App\Modules\Customization\Application\Services\OptionGroupAttacher;
use App\Modules\Customization\Domain\Repositories\OptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * Attaches a group to one product, at the end, with rules. Returns the id of the attachment.
 */
final class AttachOptionGroup
{
    public function __construct(
        private readonly CustomizableProducts $products,
        private readonly OptionGroupRepository $groups,
        private readonly OptionGroupAttacher $attacher,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(AttachOptionGroupInput $input): int
    {
        $rules = $input->rules->toRules();

        return $this->transactions->run(function () use ($input, $rules): int {
            if (! $this->products->exists($input->productId)) {
                throw EntityNotFound::withId('Product', $input->productId);
            }

            if ($this->groups->find($input->optionGroupId) === null) {
                throw ValidationFailed::forField('option_group_id', 'Choose an existing option group.');
            }

            return $this->attacher->attach($input->productId, $input->optionGroupId, $rules)
                ?? throw ValidationFailed::forField('option_group_id', 'This group is already attached to the product.');
        });
    }
}
