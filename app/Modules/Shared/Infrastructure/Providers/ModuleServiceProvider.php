<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Providers;

use Illuminate\Console\Command;
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

    protected const ADMIN_MIDDLEWARE = ['web', 'auth', 'staff'];

    protected const ACCOUNT_MIDDLEWARE = ['web', 'auth'];

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
        $this->registerConsoleCommands();
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
        foreach ($this->classesIn('Presentation/Livewire') as $relative => $class) {
            if (! is_subclass_of($class, Component::class)) {
                continue;
            }

            $parts = explode('\\', $relative);
            $alias = Str::kebab($this->moduleName()).'.'.implode('.', array_map(Str::kebab(...), $parts));

            Livewire::component($alias, $class);
        }
    }

    /**
     * Registers every Artisan command class found in Presentation/Console.
     */
    private function registerConsoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands(array_values(array_filter(
            $this->classesIn('Presentation/Console'),
            fn (string $class): bool => is_subclass_of($class, Command::class),
        )));
    }

    /**
     * The classes in a folder of this module and its sub-folders.
     *
     * @return array<string, string> name below the folder (Admin\OptionManager) => full class name
     */
    private function classesIn(string $folder): array
    {
        $directory = $this->modulePath($folder);

        if (! is_dir($directory)) {
            return [];
        }

        $namespace = 'App\\Modules\\'.$this->moduleName().'\\'.str_replace('/', '\\', $folder);
        $classes = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('/', '\\', substr($file->getPathname(), strlen($directory) + 1, -4));
            $classes[$relative] = $namespace.'\\'.$relative;
        }

        return $classes;
    }

    /**
     * Models live in Module/Infrastructure/Persistence/Models, so Laravel's
     * default guess (Database\Factories\XFactory) would not find their
     * factories. Every module registers the same closure; the test case
     * flushes factory state between tests, so it must be set again each boot.
     */
    private function registerFactoryNameResolver(): void
    {
        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            $pattern = '/^App\\\\Modules\\\\(\w+)\\\\Infrastructure\\\\Persistence\\\\Models\\\\(\w+)$/';

            if (preg_match($pattern, $modelName, $matches) === 1) {
                return "App\\Modules\\{$matches[1]}\\Infrastructure\\Database\\Factories\\{$matches[2]}Factory";
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
