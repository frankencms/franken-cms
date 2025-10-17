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
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og-default')
            ->singleFile()
            ->useDisk('public')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('og')
                    ->fit(Fit::Crop, 1200, 630)
                    ->format('jpg')
                    ->quality(85)
                    ->performOnCollections('og-default');
            });

        $this->addMediaCollection('twitter-default')
            ->singleFile()
            ->useDisk('public')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('twitter')
                    ->fit(Fit::Crop, 1200, 675)
                    ->format('jpg')
                    ->quality(85)
                    ->performOnCollections('twitter-default');
            });
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Get the media model class name
     */
    public function getMediaModel(): string
    {
        return config('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    }
}
