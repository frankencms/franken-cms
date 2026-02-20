<?php

declare(strict_types=1);

namespace FrankenCms\Listeners;

use FrankenCms\Jobs\RegeneratePostImagesJob;
use FrankenCms\Settings\MediaSettings;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelSettings\Events\SettingsSaved;

class RegeneratePostImagesListener
{
    /**
     * Handle the SettingsSaved event.
     * Queue image regeneration when media settings are updated.
     */
    public function handle(SettingsSaved $event): void
    {
        // Check if the saved settings are MediaSettings
        if ($event->settings instanceof MediaSettings) {
            Log::info('Media settings updated, queuing image regeneration...');

            // Dispatch job to regenerate all post images in the background
            RegeneratePostImagesJob::dispatch();
        }
    }
}
