<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'route_name',
        'route_parameters',
        'target',
        'css_class',
        'icon',
        'sort_order',
        'is_active',
        'linkable_type',
        'linkable_id',
        'additional_data',
    ];

    protected $casts = [
        'route_parameters' => 'array',
        'is_active' => 'boolean',
        'additional_data' => 'array',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Polymorphic relationship to linkable models (Post, Page, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the final URL for this menu item
     */
    public function getUrl(): string
    {
        // If direct URL is provided, use it
        if ($this->url) {
            return $this->url;
        }

        // If route name is provided, generate URL from route
        if ($this->route_name) {
            try {
                return route($this->route_name, $this->route_parameters ?? []);
            } catch (\Exception $e) {
                return '#';
            }
        }

        // If linkable model is provided, get URL from model
        if ($this->linkable) {
            return $this->getLinkableUrl();
        }

        return '#';
    }

    /**
     * Get URL from linkable model
     */
    protected function getLinkableUrl(): string
    {
        $linkable = $this->linkable;

        // Handle Post model
        if ($linkable instanceof Post) {
            return route('post.show', ['slug' => $linkable->post_slug]);
        }

        // Handle other models by checking for common URL methods
        if (method_exists($linkable, 'getUrl')) {
            return $linkable->getUrl();
        }

        if (method_exists($linkable, 'url')) {
            return $linkable->url();
        }

        // Fallback to slug-based URL
        if (isset($linkable->slug)) {
            return "/{$linkable->slug}";
        }

        return '#';
    }

    /**
     * Check if this menu item has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Get breadcrumb path for this menu item
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumb, $current->label);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Scope to get only active menu items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only root level menu items
     */
    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        // Clear menu cache when menu items are modified
        static::saved(function (MenuItem $menuItem) {
            $menuItem->menu->clearCache();
        });

        static::deleted(function (MenuItem $menuItem) {
            $menuItem->menu->clearCache();
        });
    }
}