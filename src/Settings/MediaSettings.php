<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class MediaSettings extends Settings
{
    // Featured Image (Single Post View)
    public string $featured_aspect_ratio = '16:9';  // Options: 16:9, 4:3, 1:1, 3:2, 21:9, custom
    public int $featured_width = 1200;              // Max width in pixels
    public ?int $featured_custom_width = null;      // Used when aspect_ratio is 'custom'
    public ?int $featured_custom_height = null;     // Used when aspect_ratio is 'custom'
    public bool $featured_crop = true;              // Crop to exact ratio

    // Listing Image (Blog Index/Archive Pages)
    public string $listing_aspect_ratio = '3:2';    // Options: 16:9, 4:3, 1:1, 3:2, 21:9, custom
    public int $listing_width = 800;                // Max width in pixels
    public ?int $listing_custom_width = null;       // Used when aspect_ratio is 'custom'
    public ?int $listing_custom_height = null;      // Used when aspect_ratio is 'custom'
    public bool $listing_crop = true;               // Crop to exact ratio

    public static function group(): string
    {
        return 'cms_media';
    }

    /**
     * Get the height for featured image based on aspect ratio
     */
    public function getFeaturedHeight(): int
    {
        if ($this->featured_aspect_ratio === 'custom') {
            return $this->featured_custom_height ?? $this->featured_width;
        }

        return $this->calculateHeight($this->featured_width, $this->featured_aspect_ratio);
    }

    /**
     * Get the height for listing image based on aspect ratio
     */
    public function getListingHeight(): int
    {
        if ($this->listing_aspect_ratio === 'custom') {
            return $this->listing_custom_height ?? $this->listing_width;
        }

        return $this->calculateHeight($this->listing_width, $this->listing_aspect_ratio);
    }

    /**
     * Calculate height from width and aspect ratio
     */
    protected function calculateHeight(int $width, string $ratio): int
    {
        $ratios = [
            '16:9' => 16 / 9,
            '4:3' => 4 / 3,
            '1:1' => 1,
            '3:2' => 3 / 2,
            '21:9' => 21 / 9,
        ];

        $divisor = $ratios[$ratio] ?? 1;

        return (int) round($width / $divisor);
    }
}