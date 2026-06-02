<?php

namespace App\Modules;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);

        foreach ($this->discoverModulePaths() as $modulePath) {
            $this->registerModule(basename((string) $modulePath), $modulePath);
        }
    }

    public function boot(): void
    {
        foreach ($this->discoverModulePaths() as $modulePath) {
            $this->bootModule(basename((string) $modulePath), $modulePath);
        }
    }

    /**
     * @return array<string>
     */
    protected function discoverModulePaths(): array
    {
        $paths = [];

        $primary = config('modules.path', app_path('Modules'));
        if (File::exists($primary)) {
            foreach (File::directories($primary) as $dir) {
                $paths[] = $dir;
            }
        }

        foreach (config('modules.external_paths', []) as $ext) {
            $resolved = base_path($ext);
            if (File::exists($resolved)) {
                foreach (File::directories($resolved) as $dir) {
                    $paths[] = $dir;
                }
            }
        }

        if (config('modules.load_composer', env('MODULES_LOAD_COMPOSER', false))) {
            $composerPath = base_path(config('modular.module_directory', 'app-modules'));
            if (File::exists($composerPath)) {
                foreach (File::directories($composerPath) as $dir) {
                    $paths[] = $dir;
                }
            }
        }

        return $paths;
    }

    protected function registerModule(string $moduleName, string $modulePath): void
    {
        $providerPath = $modulePath.'/Providers/'.$moduleName.'ServiceProvider.php';
        if (File::exists($providerPath)) {
            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $file) {
                $key = Str::snake($moduleName).'.'.$file->getFilenameWithoutExtension();
                $this->mergeConfigFrom($file->getPathname(), $key);
            }
        }

        $this->registerModuleRoutes($moduleName, $modulePath);

        $viewsPath = $modulePath.'/resources/views';
        if (File::exists($viewsPath)) {
            $this->loadViewsFrom($viewsPath, Str::snake($moduleName));
        }

        $langPath = $modulePath.'/resources/lang';
        if (File::exists($langPath)) {
            $this->loadTranslationsFrom($langPath, Str::snake($moduleName));
        }

        $migrationsPath = $modulePath.'/database/migrations';
        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function registerModuleRoutes(string $moduleName, string $modulePath): void
    {
        $routesPath = $modulePath.'/routes';

        if (! File::exists($routesPath)) {
            return;
        }

        foreach (['web', 'api', 'admin'] as $type) {
            $file = $routesPath."/{$type}.php";
            if (File::exists($file)) {
                $this->loadRoutesFrom($file);
            }
        }
    }

    protected function bootModule(string $moduleName, string $modulePath): void
    {
        $assetsPath = $modulePath.'/resources/assets';
        if (File::exists($assetsPath)) {
            $this->publishes(
                [$assetsPath => public_path("modules/{$moduleName}")],
                Str::snake($moduleName).'-assets'
            );
        }

        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $file) {
                $this->publishes(
                    [$file->getPathname() => config_path(Str::snake($moduleName).'.'.$file->getFilename())],
                    Str::snake($moduleName).'-config'
                );
            }
        }
    }
}
