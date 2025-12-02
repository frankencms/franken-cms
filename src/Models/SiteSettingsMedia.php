<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Singleton model to hold site-wide settings media files
 *
 * This model uses a single record to store default media assets for site configuration,
 * such as default SEO images (OpenGraph/Twitter), and potentially logos, favicons, etc.
 * This is NOT for user-uploaded content like post images.
 */
class SiteSettingsMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'site_settings_media';

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
            ->useDisk(config('franken-cms.media_disk_name'));

        $this->addMediaCollection('twitter-default')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));

        $this->addMediaCollection('default-featured')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
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

        // Get media settings for default featured image dimensions
        $mediaSettings = app(\FrankenCms\Settings\MediaSettings::class);

        // Default featured image - featured size (for single post view)
        $this->addMediaConversion('featured')
            ->fit($mediaSettings->featured_crop ? Fit::Crop : Fit::Max, $mediaSettings->featured_width, $mediaSettings->getFeaturedHeight())
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('default-featured');

        // Default featured image - listing size (for blog index/archive pages)
        $this->addMediaConversion('listing')
            ->fit($mediaSettings->listing_crop ? Fit::Crop : Fit::Max, $mediaSettings->listing_width, $mediaSettings->getListingHeight())
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('default-featured');
    }

    /**
     * Get the media model class name
     */
    public function getMediaModel(): string
    {
        return config('media-library.media_model', Media::class);
    }
}
