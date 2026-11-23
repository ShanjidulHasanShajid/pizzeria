<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\HomePage;

interface HomePageQuery
{
    public function current(): HomePage;
}
