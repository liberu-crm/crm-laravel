<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ModuleManager
{
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = collect();
        $this->loadModules();
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    public function enabled(): Collection
    {
        return $this->modules->filter(fn ($m) => $m->isEnabled());
    }

    public function disabled(): Collection
    {
        return $this->modules->filter(fn ($m): bool => ! $m->isEnabled());
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn ($m): bool => $m->getName() === $name);
    }

    public function has(string $name): bool
    {
        return $this->modules->contains(fn ($m): bool => $m->getName() === $name);
    }

    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->enable();

        return true;
    }

    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();

        return true;
    }

    public function install(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->install();

        return true;
    }

    public function uninstall(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot uninstall module {$name} as other modules depend on it.");
        }

        $module->uninstall();

        return true;
    }

    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    /**
     * @return array<string, array{status: string, checks: array<string, mixed>}>
     */
    public function checkHealth(): array
    {
        return $this->modules
            ->mapWithKeys(fn ($module): array => [$module->getName() => $module->checkHealth()])
            ->toArray();
    }

    public function clearCache(): void
    {
        $cacheKey = config('modules.cache_key', 'app.modules');
        Cache::forget($cacheKey);

        $this->modules->each(fn ($module) => Cache::forget("module.{$module->getName()}.enabled"));
    }

    public function rebuild(): void
    {
        $this->modules = collect();
        $this->clearCache();
        $this->loadModules();
    }

    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return [];
        }

        return [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'description' => $module->getDescription(),
            'dependencies' => $module->getDependencies(),
            'enabled' => $module->isEnabled(),
            'config' => $module->getConfig(),
        ];
    }

    public function getAllModulesInfo(): array
    {
        return $this->modules
            ->map(fn ($module): array => $this->getModuleInfo($module->getName()))
            ->values()
            ->toArray();
    }

    protected function loadModules(): void
    {
        $paths = $this->discoverModulePaths();

        foreach ($paths as $modulePath) {
            $moduleName = basename((string) $modulePath);
            $this->loadModule($moduleName, $modulePath);
        }
    }

    /**
     * Collect paths from the primary modules directory and any external paths.
     *
     * @return array<string>
     */
    protected function discoverModulePaths(): array
    {
        $paths = [];

        // Primary modules directory (app/Modules/)
        $primaryPath = config('modules.path', app_path('Modules'));
        if (File::exists($primaryPath)) {
            foreach (File::directories($primaryPath) as $dir) {
                $paths[] = $dir;
            }
        }

        // External module paths (e.g. app-modules/)
        $externalPaths = config('modules.external_paths', []);
        foreach ($externalPaths as $externalPath) {
            $resolved = base_path($externalPath);
            if (File::exists($resolved)) {
                foreach (File::directories($resolved) as $dir) {
                    $paths[] = $dir;
                }
            }
        }

        // app-modules/ convention (modular.php)
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

    protected function loadModule(string $moduleName, string $modulePath): void
    {
        // Derive the namespace from the path so external modules work too
        $isExternal = ! str_starts_with($modulePath, app_path('Modules'));
        $namespace = $isExternal
            ? config('modular.namespace', 'Modules').'\\'.basename(dirname((string) $modulePath)).'\\'.$moduleName
            : "App\\Modules\\{$moduleName}";

        $moduleClass = "{$namespace}\\{$moduleName}Module";

        if (class_exists($moduleClass)) {
            $module = new $moduleClass;
            if ($module instanceof ModuleInterface) {
                $this->register($module);
            }
        }
    }

    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dep = $this->get($dependency);
            if (! $dep || ! $dep->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    protected function hasDependents(string $moduleName): bool
    {
        return $this->enabled()->contains(
            fn ($m): bool => in_array($moduleName, $m->getDependencies())
        );
    }
}
