<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\UseCases\Navigation;

use App\Modules\Content\Application\DTOs\RenameNavItemInput;
use App\Modules\Content\Domain\Repositories\NavItemRepository;

final class RenameNavItem
{
    public function __construct(private readonly NavItemRepository $items) {}

    public function handle(RenameNavItemInput $input): void
    {
        $this->items->save($this->items->get($input->route)->rename($input->label));
    }
}
