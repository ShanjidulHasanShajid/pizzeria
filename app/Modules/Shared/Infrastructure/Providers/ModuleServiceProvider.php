<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected const STOREFRONT_MIDDLEWARE = ['web'];

    protected const ADMIN_MIDDLEWARE = ['web'];

    protected const ACCOUNT_MIDDLEWARE = ['web'];

    private static bool $factoryResolverRegistered = false;

    abstract protected function moduleName(): string;

    public function register(): void
    {
        $this->registerFactoryNameResolver();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Infrastructure/Database/Migrations'));
        $this->loadViewsFrom($this->modulePath('Presentation/Views'), $this->viewNamespace());
        $this->registerRoutes();
        $this->registerLivewireComponents();
    }

    protected function viewNamespace(): string
    {
        return Str::kebab($this->moduleName());
    }

    protected function modulePath(string $relative = ''): string
    {
        return app_path('Modules/'.$this->moduleName().($relative === '' ? '' : '/'.$relative));
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->loadModuleRoutes(
            'storefront',
            Route::middleware(self::STOREFRONT_MIDDLEWARE),
        );

        $this->loadModuleRoutes(
            'admin',
            Route::middleware(self::ADMIN_MIDDLEWARE)->prefix('admin')->name('admin.'),
        );

        $this->loadModuleRoutes(
            'account',
            Route::middleware(self::ACCOUNT_MIDDLEWARE)->prefix('account')->name('account.'),
        );
    }

    private function loadModuleRoutes(string $file, RouteRegistrar $group): void
    {
        $path = $this->modulePath("Presentation/Routes/{$file}.php");

        if (is_file($path)) {
            $group->group($path);
        }
    }

    /**
     * Registers every Livewire component class found in Presentation/Livewire
     * as "<module>.<path-in-kebab-case>", for example Catalog\...\Livewire\ProductList
     * becomes <livewire:catalog.product-list />.
     */
    private function registerLivewireComponents(): void
    {
        $directory = $this->modulePath('Presentation/Livewire');

        if (! is_dir($directory)) {
            return;
        }

        $namespace = 'App\\Modules\\'.$this->moduleName().'\\Presentation\\Livewire';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $parts = explode('\\', str_replace('/', '\\', $relative));
            $class = $namespace.'\\'.implode('\\', $parts);

            if (! is_subclass_of($class, Component::class)) {
                continue;
            }

            $alias = Str::kebab($this->moduleName()).'.'.implode('.', array_map(Str::kebab(...), $parts));

            Livewire::component($alias, $class);
        }
    }

    /**
     * Models live in Module/Infrastructure/Persistence/Models, so Laravel's
     * default guess (Database\Factories\XFactory) would not find their
     * factories. Registered once for all modules.
     */
    private function registerFactoryNameResolver(): void
    {
        if (self::$factoryResolverRegistered) {
            return;
        }

        self::$factoryResolverRegistered = true;

        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            $pattern = '/^App\\\\Modules\\\\(\w+)\\\\Infrastructure\\\\Persistence\\\\Models\\\\(\w+)$/';

            if (preg_match($pattern, $modelName, $matches) === 1) {
                return "App\\Modules\\{$matches[1]}\\Infrastructure\\Database\\Factories\\{$matches[2]}Factory";
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
