<?php

namespace Tests\Feature;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleManager $moduleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleManager = app(ModuleManager::class);
    }

    #[Test]
    public function it_can_instantiate_module_manager(): void
    {
        $this->assertInstanceOf(ModuleManager::class, $this->moduleManager);
    }

    #[Test]
    public function it_can_list_modules_as_collection(): void
    {
        $modules = $this->moduleManager->all();
        $this->assertInstanceOf(Collection::class, $modules);
    }

    #[Test]
    public function it_can_get_nonexistent_module_as_null(): void
    {
        $module = $this->moduleManager->get('NonExistentModule');
        $this->assertNull($module);
    }

    #[Test]
    public function it_returns_false_for_nonexistent_module(): void
    {
        $result = $this->moduleManager->has('NonExistentModule');
        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_empty_array_for_nonexistent_module_info(): void
    {
        $info = $this->moduleManager->getModuleInfo('NonExistentModule');
        $this->assertIsArray($info);
        $this->assertEmpty($info);
    }
}
