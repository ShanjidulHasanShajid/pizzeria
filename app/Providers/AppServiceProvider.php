<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Shared\Presentation\Support\MoneyFormatter;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
