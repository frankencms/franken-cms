<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use FrankenCms\Services\SocialLinksService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
     * Get a specific social link by platform key (legacy format compatibility)
     *
     * @deprecated Use getSocialLinks() for the new array-of-objects format
     */
    public function getSocialLink(string $key): ?string
    {
        $links = $this->social_links ?? [];

        // Support new format: array of {platform, value} objects
        if ($this->isNewSocialLinksFormat($links)) {
            foreach ($links as $link) {
                if (($link['platform'] ?? '') === $key) {
                    return $this->resolveSocialUrl($key, $link['value'] ?? '');
                }
            }

            return null;
        }

        // Legacy format: key-value pairs
        return $links[$key] ?? null;
    }

    /**
     * Set a social link (legacy format compatibility)
     *
     * @deprecated Use social_links array directly with new format
     */
    public function setSocialLink(string $key, ?string $value): void
    {
        $links = $this->social_links ?? [];

        // If using new format, update accordingly
        if ($this->isNewSocialLinksFormat($links)) {
            $found = false;
            foreach ($links as $i => $link) {
                if (($link['platform'] ?? '') === $key) {
                    $links[$i]['value'] = $value;
                    $found = true;
                    break;
                }
            }
            if (! $found && $value !== null) {
                $links[] = ['platform' => $key, 'value' => $value];
            }
        } else {
            // Legacy format
            $links[$key] = $value;
        }

        $this->social_links = $links;
    }

    /**
     * Get all social links with resolved URLs
     *
     * Returns a collection of social link objects with platform info and resolved URLs.
     *
     * @return Collection<int, array{platform: string, value: string, url: string|null, label: string, icon: string|null}>
     */
    public function getSocialLinks(): Collection
    {
        $links = $this->social_links ?? [];
        $service = app(SocialLinksService::class);

        // Handle new format: array of {platform, value} objects
        if ($this->isNewSocialLinksFormat($links)) {
            return collect($links)
                ->filter(fn ($link) => ! empty($link['platform']) && ! empty($link['value']))
                ->map(function ($link) use ($service) {
                    $platform = $link['platform'];
                    $value = $link['value'];
                    $config = $service->getPlatform($platform);

                    return [
                        'platform' => $platform,
                        'value'    => $value,
                        'url'      => $service->resolveUrl($platform, $value),
                        'label'    => $config['label'] ?? ucfirst($platform),
                        'icon'     => $service->getIcon($platform),
                    ];
                })
                ->values();
        }

        // Handle legacy format: key-value pairs
        return collect($links)
            ->filter(fn ($url, $platform) => ! empty($url))
            ->map(function ($url, $platform) use ($service) {
                $config = $service->getPlatform($platform);

                return [
                    'platform' => $platform,
                    'value'    => $url,
                    'url'      => $service->isUrl($url) ? $url : $service->resolveUrl($platform, $url),
                    'label'    => $config['label'] ?? ucfirst($platform),
                    'icon'     => $service->getIcon($platform),
                ];
            })
            ->values();
    }

    /**
     * Get the resolved URL for a social link
     */
    public function resolveSocialUrl(string $platform, string $value): ?string
    {
        return app(SocialLinksService::class)->resolveUrl($platform, $value);
    }

    /**
     * Check if social_links is in the new format (array of objects)
     *
     * New format: [['platform' => 'twitter', 'value' => 'username'], ...]
     * Legacy format: ['twitter' => 'https://...', ...]
     *
     * @param  array<mixed>  $links
     */
    protected function isNewSocialLinksFormat(array $links): bool
    {
        if (empty($links)) {
            return true; // Empty array is compatible with both
        }

        // Check if first element is an associative array with 'platform' key
        $first = reset($links);

        return is_array($first) && array_key_exists('platform', $first);
    }

    /**
     * Check if the user has any social links
     */
    public function hasSocialLinks(): bool
    {
        return $this->getSocialLinks()->isNotEmpty();
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
    public function registerMediaConversions(?Media $media = null): void
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
