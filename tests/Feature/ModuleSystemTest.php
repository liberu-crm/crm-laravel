<?php

namespace Tests\Feature;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    public function it_can_instantiate_module_manager()
    {
        $this->assertInstanceOf(ModuleManager::class, $this->moduleManager);
    }

    #[Test]
    public function it_can_list_modules_as_collection()
    {
        $modules = $this->moduleManager->all();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $modules);
    }

    #[Test]
    public function it_can_get_nonexistent_module_as_null()
    {
        $module = $this->moduleManager->get('NonExistentModule');
        $this->assertNull($module);
    }

    #[Test]
    public function it_returns_false_for_nonexistent_module()
    {
        $result = $this->moduleManager->has('NonExistentModule');
        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_empty_array_for_nonexistent_module_info()
    {
        $info = $this->moduleManager->getModuleInfo('NonExistentModule');
        $this->assertIsArray($info);
        $this->assertEmpty($info);
    }
}
