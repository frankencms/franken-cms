<?php

namespace FrankenCms\Traits;

use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Settings\PermalinkSettings;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasPermalinkUrl
{
    /**
     * Generate the post URL based on the active permalink structure.
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Pages use hierarchical URLs (e.g., /about/team/leadership)
                if ($this->post_type === 'page') {
                    return $this->getPageUrl();
                }

                $settings = app(PermalinkSettings::class);
                // Get the active permalink structure (you may need to load this from a config or settings table)
                $permalinkStructure = $settings->permalink_structure;

                // Switch to format the URL based on the structure
                return match ($permalinkStructure) {
                    PermalinkStructure::POST_NAME->value      => $this->getFormattedUrl('/%postname%'),
                    PermalinkStructure::DAY_AND_NAME->value   => $this->getFormattedUrl('/%year%/%monthnum%/%day%/%postname%'),
                    PermalinkStructure::MONTH_AND_NAME->value => $this->getFormattedUrl('/%year%/%monthnum%/%postname%'),
                    PermalinkStructure::NUMERIC->value        => $this->getFormattedUrl('/%post_id%'),
                    PermalinkStructure::CUSTOM->value         => $this->getCustomUrl(),
                    default                                   => url('/'), // Fallback to base URL
                };
            }
        );
    }

    /**
     * Generate hierarchical URL for pages
     */
    private function getPageUrl(): string
    {
        // Check if this page is the homepage
        $readingSettings = app(ReadingSettings::class);
        if ($readingSettings->home_page && $this->post_slug === $readingSettings->home_page) {
            return '/';
        }

        return $this->getHierarchicalPath();
    }

    /**
     * Generate a permalink with placeholders replaced.
     */
    private function getFormattedUrl(string $structure): string
    {

        $readingSettings = app(ReadingSettings::class);

        // Replace placeholders with actual values from the post
        $url = str_replace([
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            '%post_id%',
            '%postname%',
            '%category%',
            '%author%',
        ], [
            $this->created_at->format('Y'), // %year%
            $this->created_at->format('m'), // %monthnum%
            $this->created_at->format('d'), // %day%
            $this->created_at->format('H'), // %hour%
            $this->created_at->format('i'), // %minute%
            $this->created_at->format('s'), // %second%
            $this->id,                      // %post_id%
            $this->post_slug,               // %postname%
            $this->category->slug ?? 'uncategorized', // %category% (assuming a category relationship)
            str($this->author?->name ?? 'guest')->slug(), // %author% (posts can outlive their author)
        ], $structure);

        // Build the final URL with proper slashes
        return '/' . $readingSettings->post_page . '/' . ltrim($url, '/');
    }

    /**
     * Generate a custom permalink.
     * Custom structures should be configured dynamically.
     */
    private function getCustomUrl(): string
    {
        $settings = app(PermalinkSettings::class);

        $customStructure = implode('/', $settings->custom_permalink_structure);

        // Use the same logic as `getFormattedUrl` to replace placeholders
        return $this->getFormattedUrl($customStructure);
    }
}
