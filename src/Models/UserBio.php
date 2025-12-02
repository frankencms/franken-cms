<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserBio extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'website',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * Get the user that owns this bio
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('franken-cms.models.user'));
    }

    /**
     * Get a specific social link by key
     */
    public function getSocialLink(string $key): ?string
    {
        return $this->social_links[$key] ?? null;
    }

    /**
     * Set a social link
     */
    public function setSocialLink(string $key, ?string $value): void
    {
        $links = $this->social_links ?? [];
        $links[$key] = $value;
        $this->social_links = $links;
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Bio image - single file only
        $this->addMediaCollection('bio-image')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Square bio image (200x200) for bio display
        $this->addMediaConversion('bio-thumb')
            ->fit(Fit::Crop, 200, 200)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('bio-image');

        // Larger bio image (400x400) for higher resolution displays
        $this->addMediaConversion('bio-large')
            ->fit(Fit::Crop, 400, 400)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('bio-image');
    }
}
