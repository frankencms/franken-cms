<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get menu by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (Menu $menu) {
            $menu->clearCache();
        });

        static::deleted(function (Menu $menu) {
            $menu->clearCache();
        });
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function allMenuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->orderBy('sort_order');
    }

    /**
     * Get cached menu items with nested structure
     */
    public function getCachedMenuItems(): array
    {
        return Cache::remember(
            "menu.{$this->slug}.items",
            config('franken-cms.menu_cache', 3600),
            fn () => $this->buildMenuTree()
        );
    }

    /**
     * Clear menu cache
     */
    public function clearCache(): void
    {
        Cache::forget("menu.{$this->slug}.items");
    }

    /**
     * Build hierarchical menu tree
     */
    protected function buildMenuTree(): array
    {
        $items = $this->allMenuItems()
            ->where('is_active', true)
            ->with([
                'children',
                'linkable' => function ($morphTo) {
                    // Eager load parent hierarchy for Page models to avoid lazy loading.
                    // Limited to 3 levels deep which covers typical site structures.
                    // Deeper nesting would require recursive loading or a different approach.
                    $morphTo->morphWith([
                        Page::class => ['parent', 'parent.parent', 'parent.parent.parent'],
                    ]);
                },
            ])
            ->get()
            ->keyBy('id');

        $tree = [];

        foreach ($items as $item) {
            if ($item->parent_id === null) {
                $tree[] = $this->buildItemWithChildren($item, $items);
            }
        }

        return $tree;
    }

    /**
     * Recursively build item with its children
     */
    protected function buildItemWithChildren(MenuItem $item, $allItems): array
    {
        $itemData = [
            'id'              => $item->id,
            'label'           => $item->label,
            'url'             => $item->getUrl(),
            'target'          => $item->target,
            'additional_data' => $item->additional_data,
            'children'        => [],
        ];

        foreach ($allItems as $child) {
            if ($child->parent_id === $item->id) {
                $itemData['children'][] = $this->buildItemWithChildren($child, $allItems);
            }
        }

        return $itemData;
    }

    /**
     * Duplicate this menu with all its items
     *
     * @param  string  $newName  The name for the duplicated menu
     * @param  string  $newSlug  The slug for the duplicated menu
     * @return self The newly created menu
     */
    public function duplicateWithItems(string $newName, string $newSlug): self
    {
        return DB::transaction(function () use ($newName, $newSlug) {
            // Create the new menu
            $newMenu = static::create([
                'name'      => $newName,
                'slug'      => $newSlug,
                'is_active' => $this->is_active,
            ]);

            // Get all menu items for this menu, ordered by parent_id to ensure parents are processed first
            $items = $this->allMenuItems()
                ->orderByRaw('parent_id IS NOT NULL, parent_id')
                ->orderBy('sort_order')
                ->get();

            // Map old item IDs to new item IDs for parent relationship mapping
            $idMap = [];

            foreach ($items as $item) {
                $newItem = MenuItem::create([
                    'menu_id'          => $newMenu->id,
                    'parent_id'        => $item->parent_id ? ($idMap[$item->parent_id] ?? null) : null,
                    'label'            => $item->label,
                    'url'              => $item->url,
                    'route_name'       => $item->route_name,
                    'route_parameters' => $item->route_parameters,
                    'linkable_type'    => $item->linkable_type,
                    'linkable_id'      => $item->linkable_id,
                    'target'           => $item->target,
                    'additional_data'  => $item->additional_data,
                    'sort_order'       => $item->sort_order,
                    'is_active'        => $item->is_active,
                ]);

                // Store mapping of old ID to new ID
                $idMap[$item->id] = $newItem->id;
            }

            return $newMenu;
        });
    }
}
