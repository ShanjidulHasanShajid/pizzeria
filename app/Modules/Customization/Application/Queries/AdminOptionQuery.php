<?php

declare(strict_types=1);

namespace App\Modules\Customization\Application\Queries;

use App\Modules\Customization\Application\DTOs\AdminOptionGroupDetail;
use App\Modules\Customization\Application\DTOs\AttachedGroupRow;
use App\Modules\Customization\Application\DTOs\OptionGroupRow;

/**
 * Read side of the option groups for the admin screens.
 */
interface AdminOptionQuery
{
    /**
     * Groups that are not deleted, in sort order.
     *
     * @return list<OptionGroupRow>
     */
    public function groupTable(): array;

    /** One group that is not deleted, with its options that are not deleted; or null. */
    public function group(int $id): ?AdminOptionGroupDetail;

    /**
     * Every group that is not deleted, with its options, in sort order.
     *
     * @return list<AdminOptionGroupDetail>
     */
    public function groups(): array;

    /**
     * The groups attached to one product, in the product's order. Inactive groups are included and marked.
     *
     * @return list<AttachedGroupRow>
     */
    public function attachedGroups(int $productId): array;
}
