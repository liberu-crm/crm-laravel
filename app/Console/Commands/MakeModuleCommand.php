<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The module name (PascalCase)} {--force : Overwrite existing module}';

    protected $description = 'Create a new module scaffold';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $modulePath = app_path("Modules/{$name}Module");

        if (File::exists($modulePath) && ! $this->option('force')) {
            $this->error("Module '{$name}' already exists. Use --force to overwrite.");
            return 1;
        }

        $this->createStructure($name, $modulePath);
        $this->info("Module '{$name}' created successfully at app/Modules/{$name}Module/");
        $this->line("  Register its service provider in config/app.php or a service provider.");

        return 0;
    }

    private function createStructure(string $name, string $modulePath): void
    {
        $dirs = [
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Providers',
            'Services',
            'resources/views',
            'resources/lang',
            'routes',
            'database/migrations',
            'database/seeders',
            'config',
            'tests',
        ];

        foreach ($dirs as $dir) {
            File::makeDirectory("{$modulePath}/{$dir}", 0755, true, true);
        }

        File::put("{$modulePath}/module.json", json_encode([
            'name' => $name,
            'version' => '1.0.0',
            'description' => "{$name} module for Liberu CRM",
            'dependencies' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put("{$modulePath}/{$name}Module.php", $this->moduleClassStub($name));
        File::put("{$modulePath}/Providers/{$name}ServiceProvider.php", $this->serviceProviderStub($name));
        File::put("{$modulePath}/routes/web.php", "<?php\n\n// {$name} module web routes\n");
        File::put("{$modulePath}/routes/api.php", "<?php\n\n// {$name} module API routes\n");
        File::put("{$modulePath}/config/{$name}.php", "<?php\n\nreturn [\n    // {$name} module config\n];\n");
    }

    private function moduleClassStub(string $name): string
    {
        return <<<PHP
<?php

namespace App\Modules\\{$name}Module;

use App\Modules\BaseModule;

class {$name}Module extends BaseModule
{
    protected function onEnable(): void {}

    protected function onDisable(): void {}

    protected function onInstall(): void {}

    protected function onUninstall(): void {}
}
PHP;
    }

    private function serviceProviderStub(string $name): string
    {
        $lower = Str::snake($name);

        return <<<PHP
<?php

namespace App\Modules\\{$name}Module\Providers;

use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(__DIR__ . '/../config/{$lower}.php', '{$lower}');
    }

    public function boot(): void
    {
        \$this->loadViewsFrom(__DIR__ . '/../resources/views', '{$lower}');
        \$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if (\$this->app->routesAreCached()) {
            return;
        }

        \$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        \$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
PHP;
    }
}
