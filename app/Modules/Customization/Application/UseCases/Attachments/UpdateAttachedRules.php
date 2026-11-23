<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Attachments;

use App\Modules\Customization\Application\DTOs\UpdateAttachedRulesInput;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Changes the rules of a group on one product. Other products keep theirs.
 */
final class UpdateAttachedRules
{
    public function __construct(
        private readonly ProductOptionGroupRepository $attachments,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(UpdateAttachedRulesInput $input): void
    {
        $rules = $input->rules->toRules();

        $this->transactions->run(function () use ($input, $rules): void {
            $attachment = $this->attachments->find($input->attachmentId);

            // An attachment of another product counts as "not found", like an unknown id.
            if ($attachment === null || $attachment->productId() !== $input->productId) {
                throw EntityNotFound::withId('Attached option group', $input->attachmentId);
            }

            $attachment->changeRules($rules);
            $this->attachments->save($attachment);
        });
    }
}
