<?php

declare(strict_types=1);

namespace FrankenCms\Listeners;

use FrankenCms\Services\RobotsService;
use FrankenCms\Settings\RobotsSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class ClearRobotsCacheListener
{
    public function __construct(
        protected RobotsService $robotsService
    ) {}

    /**
     * Handle the SettingsSaved event.
     * Clear robots.txt cache when robots settings are updated.
     */
    public function handle(SettingsSaved $event): void
    {
        // Check if the saved settings are RobotsSettings
        if ($event->settings instanceof RobotsSettings) {
            $this->robotsService->clearCache();
        }
    }
}
