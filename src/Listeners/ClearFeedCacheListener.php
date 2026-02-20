<?php

declare(strict_types=1);

namespace FrankenCms\Listeners;

use FrankenCms\Services\FeedService;
use FrankenCms\Settings\ReadingSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class ClearFeedCacheListener
{
    public function __construct(
        protected FeedService $feedService
    ) {}

    /**
     * Handle the SettingsSaved event.
     * Clear feed cache when reading settings are updated.
     */
    public function handle(SettingsSaved $event): void
    {
        // Check if the saved settings are ReadingSettings
        if ($event->settings instanceof ReadingSettings) {
            $this->feedService->clearCache();
        }
    }
}
