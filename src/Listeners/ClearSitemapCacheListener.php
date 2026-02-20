<?php

declare(strict_types=1);

namespace FrankenCms\Listeners;

use FrankenCms\Services\SitemapService;
use FrankenCms\Settings\SitemapSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class ClearSitemapCacheListener
{
    public function __construct(
        protected SitemapService $sitemapService
    ) {}

    /**
     * Handle the SettingsSaved event.
     * Clear sitemap cache when sitemap settings are updated.
     */
    public function handle(SettingsSaved $event): void
    {
        // Check if the saved settings are SitemapSettings
        if ($event->settings instanceof SitemapSettings) {
            $this->sitemapService->clearCache();
        }
    }
}
