<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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
            ->with('children')
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
            'is_active'       => $item->is_active,
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
}
