<?php

namespace App\Modules;

use App\Events\Module\ModuleDisabled;
use App\Events\Module\ModuleEnabled;
use App\Events\Module\ModuleInstalled;
use App\Events\Module\ModuleUninstalled;
use App\Modules\Contracts\ModuleInterface;
use Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionClass;

abstract class BaseModule implements ModuleInterface
{
    protected string $name;

    protected string $version = '1.0.0';

    protected string $description = '';

    protected array $dependencies = [];

    protected array $config = [];

    public function __construct()
    {
        $this->loadModuleInfo();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        if ($this->isDevelopmentMode()) {
            // Skip the cache layer entirely so state changes reflect immediately.
            // (enable()/disable() forget this key, so reading it here always
            // returned the default and never reflected the persisted state.)
            return $this->resolveEnabledState();
        }

        $ttl = config('modules.cache_ttl', 3600);

        return Cache::remember(
            "module.{$this->name}.enabled",
            $ttl,
            fn () => $this->resolveEnabledState()
        );
    }

    public function enable(): void
    {
        $this->persistState(true);
        Cache::forget("module.{$this->name}.enabled");
        $this->onEnable();
        ModuleEnabled::dispatch($this);
    }

    public function disable(): void
    {
        $this->persistState(false);
        Cache::forget("module.{$this->name}.enabled");
        $this->onDisable();
        ModuleDisabled::dispatch($this);
    }

    public function install(): void
    {
        $this->runMigrations();
        $this->publishAssets();
        $this->onInstall();
        $this->enable();
        ModuleInstalled::dispatch($this);
    }

    public function uninstall(): void
    {
        $this->disable();
        $this->rollbackMigrations();
        $this->removeAssets();
        $this->onUninstall();
        ModuleUninstalled::dispatch($this);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array{status: string, checks: array<string, mixed>}
     */
    public function checkHealth(): array
    {
        $modulePath = $this->getModulePath();
        $checks = [
            'module_json_exists' => File::exists($modulePath.'/module.json'),
            'is_enabled' => $this->isEnabled(),
            'version' => $this->version,
        ];

        $status = collect($checks)->contains(false) ? 'degraded' : 'healthy';

        return compact('status', 'checks');
    }

    protected function loadModuleInfo(): void
    {
        $modulePath = $this->getModulePath();
        $moduleInfoPath = $modulePath.'/module.json';

        if (File::exists($moduleInfoPath)) {
            $moduleInfo = json_decode(File::get($moduleInfoPath), true) ?? [];
            $this->name = $moduleInfo['name'] ?? class_basename($this);
            $this->version = $moduleInfo['version'] ?? '1.0.0';
            $this->description = $moduleInfo['description'] ?? '';
            $this->dependencies = $moduleInfo['dependencies'] ?? [];
            $this->config = $moduleInfo['config'] ?? [];
        } else {
            $this->name = class_basename($this);
        }
    }

    protected function getModulePath(): string
    {
        return dirname((new ReflectionClass($this))->getFileName());
    }

    protected function isDevelopmentMode(): bool
    {
        return (bool) config('modules.development', false);
    }

    /**
     * Resolve enabled state from DB (with graceful fallback to config defaults).
     */
    protected function resolveEnabledState(): bool
    {
        try {
            $record = \App\Models\Module::query()->where('name', $this->name)->first();

            if ($record) {
                return (bool) $record->is_enabled;
            }
        } catch (\Exception) {
            // DB not ready yet
        }

        $defaults = config('modules.enabled', []);

        return empty($defaults) || in_array($this->name, $defaults);
    }

    protected function persistState(bool $enabled): void
    {
        try {
            \App\Models\Module::query()->updateOrCreate(
                ['name' => $this->name],
                [
                    'is_enabled' => $enabled,
                    'installed_at' => $enabled ? now() : null,
                ]
            );
        } catch (\Exception) {
            // Fallback to cache-only when DB unavailable
            Cache::put("module.{$this->name}.enabled", $enabled);
        }
    }

    protected function runMigrations(): void
    {
        $migrationsPath = $this->getModulePath().'/database/migrations';

        if (File::exists($migrationsPath)) {
            Artisan::call('migrate', [
                '--path' => 'app/Modules/'.$this->name.'/database/migrations',
                '--force' => true,
            ]);
        }
    }

    protected function rollbackMigrations(): void {}

    protected function publishAssets(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => strtolower($this->name).'-assets',
            '--force' => true,
        ]);
    }

    protected function removeAssets(): void
    {
        $assetsPath = public_path("modules/{$this->name}");
        if (File::exists($assetsPath)) {
            File::deleteDirectory($assetsPath);
        }
    }

    protected function onEnable(): void {}

    protected function onDisable(): void {}

    protected function onInstall(): void {}

    protected function onUninstall(): void {}
}
