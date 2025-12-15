<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Exception;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'route_parameters' => 'array',
        'is_active'        => 'boolean',
        'additional_data'  => 'array',
        'sort_order'       => 'integer',
    ];

    protected $with = ['linkable'];

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
        // If linkable model is provided, compute URL from model (supports dynamic homepage)
        if ($this->linkable) {
            return $this->getLinkableUrl();
        }

        // If direct URL is provided, use it (custom URLs)
        if ($this->url) {
            // Convert relative URLs to full URLs
            if (str_starts_with($this->url, '/')) {
                return url($this->url);
            }

            return $this->url;
        }

        // If route name is provided, generate URL from route
        if ($this->route_name) {
            try {
                return route($this->route_name, $this->route_parameters ?? []);
            } catch (Exception $e) {
                return '#';
            }
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
        // TODO: Implement this with proper breadcrumb components

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

    /**
     * Get URL from linkable model
     */
    protected function getLinkableUrl(): string
    {
        $linkable = $this->linkable;

        // Page/Post models use HasPermalinkUrl trait which provides a 'url' attribute
        // This handles homepage detection, permalink structures, etc.
        if ($linkable instanceof Post || $linkable instanceof Page) {
            return url($linkable->url);
        }

        // Handle other models by checking for common URL methods
        if (method_exists($linkable, 'getUrl')) {
            $linkableUrl = $linkable->getUrl();

            return str_starts_with($linkableUrl, '/') ? url($linkableUrl) : $linkableUrl;
        }

        if (method_exists($linkable, 'url')) {
            $linkableUrl = $linkable->url();

            return str_starts_with($linkableUrl, '/') ? url($linkableUrl) : $linkableUrl;
        }

        // Fallback to slug-based URL
        if (isset($linkable->slug)) {
            return url("/{$linkable->slug}");
        }

        return '#';
    }
}
