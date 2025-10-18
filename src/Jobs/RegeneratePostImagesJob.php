<?php

declare(strict_types=1);

namespace FrankenCms\Jobs;

use FrankenCms\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RegeneratePostImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all media from the featured collection
        $mediaItems = Media::where('collection_name', 'featured')
            ->where('model_type', Post::class)
            ->get();

        $count = $mediaItems->count();

        if ($count === 0) {
            return;
        }

        // Collect all media IDs
        $mediaIds = $mediaItems->pluck('id')->implode(',');

        try {
            // Call artisan command to regenerate conversions for all media items
            Artisan::call('media-library:regenerate', [
                '--ids' => $mediaIds,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to queue regeneration: {$e->getMessage()}");
            throw $e;
        }
    }
}
