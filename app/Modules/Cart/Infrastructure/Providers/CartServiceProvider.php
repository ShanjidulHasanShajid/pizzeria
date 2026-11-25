<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Providers;

use App\Modules\Cart\Application\Listeners\MergeCartOnLogin;
use App\Modules\Cart\Application\Ports\CartContext;
use App\Modules\Cart\Application\Ports\CartProducts;
use App\Modules\Cart\Application\Ports\LinePricing;
use App\Modules\Cart\Application\Queries\CartCountQuery;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Infrastructure\Adapters\CatalogCartProducts;
use App\Modules\Cart\Infrastructure\Adapters\CookieCartContext;
use App\Modules\Cart\Infrastructure\Adapters\CustomizationLinePricing;
use App\Modules\Cart\Infrastructure\Persistence\Repositories\EloquentCartRepository;
use App\Modules\Cart\Infrastructure\Queries\EloquentCartCountQuery;
use App\Modules\Identity\Application\Events\UserLoggedIn;
use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

final class CartServiceProvider extends ModuleServiceProvider
{
    /**
     * Interface => implementation pairs for this module.
     * Laravel registers this array automatically.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CartRepository::class => EloquentCartRepository::class,
        CartContext::class => CookieCartContext::class,
        LinePricing::class => CustomizationLinePricing::class,
        CartProducts::class => CatalogCartProducts::class,
        CartCountQuery::class => EloquentCartCountQuery::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Event::listen(UserLoggedIn::class, MergeCartOnLogin::class);
    }

    protected function moduleName(): string
    {
        return 'Cart';
    }
}
