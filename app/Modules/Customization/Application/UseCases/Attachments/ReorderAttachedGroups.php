<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Attachments;

use App\Modules\Customization\Application\DTOs\ReorderAttachedGroupsInput;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * The order in which a product shows its groups. The list must name every attached group once.
 */
final class ReorderAttachedGroups
{
    public function __construct(
        private readonly ProductOptionGroupRepository $attachments,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ReorderAttachedGroupsInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $attached = [];

            foreach ($this->attachments->forProduct($input->productId) as $attachment) {
                $attached[(int) $attachment->id()] = $attachment;
            }

            $expected = array_keys($attached);
            $given = $input->attachmentIds;
            sort($expected);
            sort($given);

            if ($given !== $expected) {
                throw ValidationFailed::forField('ids', 'The list must contain every attached group once. Reload the page and try again.');
            }

            $sort = SortOrder::first();

            foreach ($input->attachmentIds as $id) {
                $attached[$id]->moveTo($sort);
                $this->attachments->save($attached[$id]);
                $sort = $sort->next();
            }
        });
    }
}
