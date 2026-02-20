<?php

use FrankenCms\Models\Menu;
use FrankenCms\Models\MenuItem;

describe('Menu duplication', function () {

    it('can duplicate a menu without items', function () {
        $menu = Menu::create([
            'name'      => 'Original Menu',
            'slug'      => 'original-menu',
            'is_active' => true,
        ]);

        $duplicated = $menu->duplicateWithItems('Duplicated Menu', 'duplicated-menu');

        expect($duplicated)->toBeInstanceOf(Menu::class);
        expect($duplicated->name)->toBe('Duplicated Menu');
        expect($duplicated->slug)->toBe('duplicated-menu');
        expect($duplicated->is_active)->toBe($menu->is_active);
        expect($duplicated->id)->not->toBe($menu->id);
        expect($duplicated->allMenuItems()->count())->toBe(0);
    });

    it('can duplicate a menu with items', function () {
        $menu = Menu::create([
            'name'      => 'Main Menu',
            'slug'      => 'main-menu',
            'is_active' => true,
        ]);

        // Create some menu items
        $item1 = MenuItem::create([
            'menu_id'    => $menu->id,
            'label'      => 'Home',
            'url'        => '/',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        $item2 = MenuItem::create([
            'menu_id'    => $menu->id,
            'label'      => 'About',
            'url'        => '/about',
            'sort_order' => 2,
            'is_active'  => true,
        ]);

        $item3 = MenuItem::create([
            'menu_id'    => $menu->id,
            'label'      => 'Contact',
            'url'        => '/contact',
            'sort_order' => 3,
            'is_active'  => false,
        ]);

        $duplicated = $menu->duplicateWithItems('Footer Menu', 'footer-menu');

        expect($duplicated->name)->toBe('Footer Menu');
        expect($duplicated->slug)->toBe('footer-menu');
        expect($duplicated->allMenuItems()->count())->toBe(3);

        // Verify items were duplicated with correct data
        $duplicatedItems = $duplicated->allMenuItems()->orderBy('sort_order')->get();

        expect($duplicatedItems[0]->label)->toBe('Home');
        expect($duplicatedItems[0]->url)->toBe('/');
        expect($duplicatedItems[0]->is_active)->toBeTrue();

        expect($duplicatedItems[1]->label)->toBe('About');
        expect($duplicatedItems[1]->url)->toBe('/about');

        expect($duplicatedItems[2]->label)->toBe('Contact');
        expect($duplicatedItems[2]->is_active)->toBeFalse();

        // Ensure duplicated items belong to the new menu
        foreach ($duplicatedItems as $item) {
            expect($item->menu_id)->toBe($duplicated->id);
        }
    });

    it('can duplicate a menu with nested items preserving hierarchy', function () {
        $menu = Menu::create([
            'name'      => 'Nested Menu',
            'slug'      => 'nested-menu',
            'is_active' => true,
        ]);

        // Create parent item
        $parent = MenuItem::create([
            'menu_id'    => $menu->id,
            'label'      => 'Products',
            'url'        => '/products',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        // Create child items
        $child1 = MenuItem::create([
            'menu_id'    => $menu->id,
            'parent_id'  => $parent->id,
            'label'      => 'Category A',
            'url'        => '/products/category-a',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        $child2 = MenuItem::create([
            'menu_id'    => $menu->id,
            'parent_id'  => $parent->id,
            'label'      => 'Category B',
            'url'        => '/products/category-b',
            'sort_order' => 2,
            'is_active'  => true,
        ]);

        // Create grandchild
        $grandchild = MenuItem::create([
            'menu_id'    => $menu->id,
            'parent_id'  => $child1->id,
            'label'      => 'Subcategory',
            'url'        => '/products/category-a/subcategory',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        $duplicated = $menu->duplicateWithItems('Duplicated Nested', 'duplicated-nested');

        expect($duplicated->allMenuItems()->count())->toBe(4);

        // Get the duplicated parent
        $dupParent = $duplicated->allMenuItems()->whereNull('parent_id')->first();
        expect($dupParent)->not->toBeNull();
        expect($dupParent->label)->toBe('Products');

        // Get children of the duplicated parent
        $dupChildren = $duplicated->allMenuItems()->where('parent_id', $dupParent->id)->get();
        expect($dupChildren)->toHaveCount(2);

        // Find Category A child
        $dupChild1 = $dupChildren->firstWhere('label', 'Category A');
        expect($dupChild1)->not->toBeNull();

        // Get grandchild of duplicated Category A
        $dupGrandchild = $duplicated->allMenuItems()->where('parent_id', $dupChild1->id)->first();
        expect($dupGrandchild)->not->toBeNull();
        expect($dupGrandchild->label)->toBe('Subcategory');
    });

    it('preserves additional data when duplicating', function () {
        $menu = Menu::create([
            'name'      => 'Menu with Data',
            'slug'      => 'menu-with-data',
            'is_active' => true,
        ]);

        $item = MenuItem::create([
            'menu_id'         => $menu->id,
            'label'           => 'Special Item',
            'url'             => '/special',
            'target'          => '_blank',
            'additional_data' => ['class' => 'highlighted', 'badge' => 'new'],
            'sort_order'      => 1,
            'is_active'       => true,
        ]);

        $duplicated = $menu->duplicateWithItems('Copy with Data', 'copy-with-data');
        $dupItem = $duplicated->allMenuItems()->first();

        expect($dupItem->target)->toBe('_blank');
        expect($dupItem->additional_data)->toBe(['class' => 'highlighted', 'badge' => 'new']);
    });

});
