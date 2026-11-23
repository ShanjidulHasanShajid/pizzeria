<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Catalog\Presentation\Support\CatalogTrash;
use App\Modules\Content\Presentation\Support\ContentTrash;
use App\Modules\Content\Presentation\Support\StorefrontLayoutComposer;
use App\Modules\Customization\Presentation\Support\OptionTrash;
use App\Modules\Shared\Presentation\Http\Controllers\Admin\TrashController;
use App\Modules\Shared\Presentation\Support\MoneyFormatter;
use App\Modules\Shared\Presentation\Support\TrashSource;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Every module with deletable records adds its source here (the tabs appear in this order).
        $this->app->tag([CatalogTrash::class, OptionTrash::class, ContentTrash::class], TrashSource::class);
        $this->app->when(TrashController::class)->needs('$sources')->giveTagged(TrashSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // On Windows the variable is named "SystemRoot", but `artisan serve` only
        // passes through "SYSTEMROOT". Without it, Winsock can't open a socket.
        if (PHP_OS_FAMILY === 'Windows') {
            ServeCommand::$passthroughVariables[] = 'SystemRoot';
        }

        // @money($price) in any Blade view prints poisha as ৳1,250.
        Blade::directive('money', function (string $expression): string {
            return '<?php echo e(app(\''.MoneyFormatter::class.'\')->format('.$expression.')); ?>';
        });

        // Every ->links() call uses our branded pagination view.
        Paginator::defaultView('vendor.pagination.brand');

        // Settings, navbar, mega menu and branches for every shop page.
        View::composer('components.layouts.storefront', StorefrontLayoutComposer::class);
    }
}
