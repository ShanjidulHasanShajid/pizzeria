<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\UseCases\Attachments;

use App\Modules\Customization\Application\DTOs\DetachOptionGroupInput;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * Takes a group off one product. The group itself stays.
 */
final class DetachOptionGroup
{
    public function __construct(
        private readonly ProductOptionGroupRepository $attachments,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(DetachOptionGroupInput $input): void
    {
        $this->transactions->run(function () use ($input): void {
            $attachment = $this->attachments->find($input->attachmentId);

            if ($attachment === null || $attachment->productId() !== $input->productId) {
                throw EntityNotFound::withId('Attached option group', $input->attachmentId);
            }

            $this->attachments->delete($input->attachmentId);
        });
    }
}
