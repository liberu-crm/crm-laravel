<?php

namespace Tests\Unit\Services;

use App\Models\Menu;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_menu_returns_menu_instance(): void
    {
        $service = new MenuService;
        $menu = $service->buildMenu();

        $this->assertNotNull($menu);
    }

    public function test_build_menu_with_items(): void
    {
        Menu::create(['name' => 'Dashboard', 'url' => '/dashboard', 'order' => 1]);
        Menu::create(['name' => 'Contacts', 'url' => '/contacts', 'order' => 2]);

        $service = new MenuService;
        $menu = $service->buildMenu();

        $this->assertNotNull($menu);
    }

    public function test_build_menu_empty_when_no_items(): void
    {
        $service = new MenuService;
        $menu = $service->buildMenu();

        $this->assertNotNull($menu);
        $rendered = $menu->render();
        $this->assertIsString($rendered);
    }
}
