<?php

declare(strict_types=1);

namespace FrankenCms\Listeners;

use FrankenCms\Models\Menu;
use FrankenCms\Services\PageRouteService;
use FrankenCms\Settings\ReadingSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class ClearMenuCacheListener
{
    public function __construct(
        protected PageRouteService $pageRouteService
    ) {}

    /**
     * Handle the SettingsSaved event.
     * Clear all menu and route caches when reading settings are updated (homepage may have changed).
     */
    public function handle(SettingsSaved $event): void
    {
        // Check if the saved settings are ReadingSettings
        if ($event->settings instanceof ReadingSettings) {
            // Clear cache for all menus since homepage URLs may have changed
            Menu::all()->each(fn (Menu $menu) => $menu->clearCache());

            // Clear page route cache so homepage redirect takes effect
            $this->pageRouteService->clearCache();
        }
    }
}
