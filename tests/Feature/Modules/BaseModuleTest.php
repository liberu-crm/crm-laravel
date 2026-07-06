<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Events\Module\ModuleDisabled;
use App\Events\Module\ModuleEnabled;
use App\Models\Module;
use App\Modules\BaseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BaseModule is abstract; this named stub gives class_basename() a stable
 * module name ('BaseModuleFake') which BaseModule uses as the DB/cache key.
 */
class BaseModuleFake extends BaseModule {}

class BaseModuleTest extends TestCase
{
    use RefreshDatabase;

    protected BaseModuleFake $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new BaseModuleFake;
    }

    #[Test]
    public function enable_persists_state_forgets_cache_and_fires_event(): void
    {
        Event::fake();
        Cache::put('module.BaseModuleFake.enabled', false); // stale value must be dropped

        $this->module->enable();

        $this->assertDatabaseHas('modules', ['name' => 'BaseModuleFake', 'is_enabled' => 1]);
        $this->assertFalse(Cache::has('module.BaseModuleFake.enabled'));
        Event::assertDispatched(ModuleEnabled::class, fn ($e): bool => $e->module === $this->module);
    }

    #[Test]
    public function disable_persists_state_forgets_cache_and_fires_event(): void
    {
        Event::fake();
        Cache::put('module.BaseModuleFake.enabled', true);

        $this->module->disable();

        $this->assertDatabaseHas('modules', ['name' => 'BaseModuleFake', 'is_enabled' => 0]);
        $this->assertFalse(Cache::has('module.BaseModuleFake.enabled'));
        Event::assertDispatched(ModuleDisabled::class, fn ($e): bool => $e->module === $this->module);
    }

    #[Test]
    public function is_enabled_resolves_from_the_module_row(): void
    {
        config(['modules.development' => false]); // exercise the cached DB-resolve path

        Module::create(['name' => 'BaseModuleFake', 'is_enabled' => false]);
        Cache::forget('module.BaseModuleFake.enabled');
        $this->assertFalse($this->module->isEnabled());

        Module::query()->where('name', 'BaseModuleFake')->update(['is_enabled' => true]);
        Cache::forget('module.BaseModuleFake.enabled'); // bust remember() cache
        $this->assertTrue($this->module->isEnabled());
    }

    #[Test]
    public function is_enabled_reflects_db_immediately_in_development_mode(): void
    {
        config(['modules.development' => true]);

        $this->module->disable(); // persists is_enabled=false and forgets the cache key
        $this->assertFalse($this->module->isEnabled());

        $this->module->enable();
        $this->assertTrue($this->module->isEnabled());
    }

    #[Test]
    public function check_health_returns_status_and_checks_shape(): void
    {
        config(['modules.development' => true]);
        $this->module->enable();

        $health = $this->module->checkHealth();

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertContains($health['status'], ['healthy', 'degraded']);
        $this->assertTrue($health['checks']['is_enabled']);
        $this->assertSame('1.0.0', $health['checks']['version']);
        $this->assertArrayHasKey('module_json_exists', $health['checks']);
    }
}
