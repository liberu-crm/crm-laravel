<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $menus = [
            [
                'name' => 'Home',
                'url' => '/',
                'order' => 1
            ],
            [
                'name' => 'Submit a Ticket',
                'url' => '/#submit-ticket',
                'order' => 2
            ],
            [
                'name' => 'Knowledge Base',
                'url' => '/knowledge-base',
                'order' => 3
            ],
            [
                'name' => 'Request a Quote',
                'url' => '/#request-quote',
                'order' => 4
            ],
            [
                'name' => 'Properties',
                'url' => '/properties',
                'order' => 5,
                'children' => [
                    ['name' => 'For Sale', 'url' => '/properties/for-sale', 'order' => 1],
                    ['name' => 'For Rent', 'url' => '/properties/for-rent', 'order' => 2],
                ]
            ],
            [
                'name' => 'Services',
                'url' => '/services',
                'order' => 6,
                'children' => [
                    ['name' => 'Buying', 'url' => '/services/buying', 'order' => 1],
                    ['name' => 'Selling', 'url' => '/services/selling', 'order' => 2],
                    ['name' => 'Renting', 'url' => '/services/renting', 'order' => 3],
                ]
            ],
            [
                'name' => 'About',
                'url' => '/about',
                'order' => 7
            ],
            [
                'name' => 'Contact',
                'url' => '/contact',
                'order' => 8
            ],
            [
                'name' => 'Calculators',
                'url' => '/calculators',
                'order' => 9
            ],
        ];

        foreach ($menus as $menuData) {
            $this->createMenu($menuData);
        }
    }

    private function createMenu($menuData, $parentId = null)
    {
        $children = $menuData['children'] ?? [];
        unset($menuData['children']);

        $menuData['parent_id'] = $parentId;
        $menu = Menu::create($menuData);

        foreach ($children as $childData) {
            $this->createMenu($childData, $menu->id);
        }
    }
}