<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Singleton model to hold SEO-related media files
 * This model uses a single record to store all default SEO images
 */
class SeoMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'seo_media';

    protected $fillable = [];

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og-default')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('twitter-default')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // OpenGraph default image - exact 1200x630 dimensions (no focal point needed)
        $this->addMediaConversion('og')
            ->fit(Fit::Crop, 1200, 630)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('og-default');

        // Twitter conversion for OG images - always 1200x675 for large image cards
        $this->addMediaConversion('twitter')
            ->fit(Fit::Crop, 1200, 675)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('og-default');

        // Twitter summary card conversion - 600x600 square for dedicated summary images
        $this->addMediaConversion('twitter-summary')
            ->fit(Fit::Crop, 600, 600)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('twitter-default');
    }

    /**
     * Get the media model class name
     */
    public function getMediaModel(): string
    {
        return config('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    }
}
