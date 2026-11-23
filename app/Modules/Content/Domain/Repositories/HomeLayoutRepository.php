<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\ValueObjects\HomeLayout;

interface HomeLayoutRepository
{
    public function get(): HomeLayout;

    /** Replaces the whole layout. */
    public function save(HomeLayout $layout): void;
}
