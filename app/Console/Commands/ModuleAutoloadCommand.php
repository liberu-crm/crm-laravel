<?php

namespace App\Console\Commands;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;

class ModuleAutoloadCommand extends Command
{
    protected $signature = 'module:autoload {--clear : Clear the module cache}';

    protected $description = 'Rebuild the module autoload cache';

    public function handle(ModuleManager $manager): int
    {
        if ($this->option('clear')) {
            $manager->clearCache();
            $this->info('Module cache cleared.');
        }

        $manager->rebuild();
        $modules = $manager->all();

        $this->info("Module autoload rebuilt. Found {$modules->count()} module(s):");

        $modules->each(function ($module) {
            $status = $module->isEnabled() ? '<fg=green>enabled</>' : '<fg=yellow>disabled</>';
            $this->line("  [{$status}] {$module->getName()} v{$module->getVersion()}");
        });

        return 0;
    }
}
