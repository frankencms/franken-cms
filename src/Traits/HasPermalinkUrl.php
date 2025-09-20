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
                $settings = app(PermalinkSettings::class);
                // Get the active permalink structure (you may need to load this from a config or settings table)
                $permalinkStructure = $settings->permalink_structure;

                // Switch to format the URL based on the structure
                return match ($permalinkStructure) {
                    PermalinkStructure::PLAIN->value          => $this->getPlainUrl(),
                    PermalinkStructure::DAY_AND_NAME->value   => $this->getFormattedUrl('/%year%/%monthnum%/%day%/%postname%'),
                    PermalinkStructure::MONTH_AND_NAME->value => $this->getFormattedUrl('/%year%/%monthnum%/%postname%'),
                    PermalinkStructure::NUMERIC->value        => $this->getFormattedUrl('/archives/%post_id%'),
                    PermalinkStructure::POST_NAME->value      => $this->getFormattedUrl('/%postname%/'),
                    PermalinkStructure::CUSTOM->value         => $this->getCustomUrl(),
                    default                                   => url('/'), // Fallback to base URL
                };
            }
        );
    }

    /**
     * Generate "Plain" permalink structure: ?p=123
     */
    private function getPlainUrl(): string
    {
        //        return url('/?p=' . $this->id); // Use the post ID for plain URLs
        return '/?p=' . $this->id; // Use the post ID for plain URLs
    }

    /**
     * Generate a permalink with placeholders replaced.
     */
    private function getFormattedUrl(string $structure): string
    {

        $readingSettings = app(ReadingSettings::class);

        // Replace placeholders with actual values from the post
        return $readingSettings->post_page . str_replace([
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
            str($this->author->name)->slug() ?? 'guest',           // %author% (assuming an author relationship)
        ], $structure);
    }

    /**
     * Generate a custom permalink.
     * Custom structures should be configured dynamically.
     */
    private function getCustomUrl(): string
    {
        $settings = app(PermalinkSettings::class);

        $customStructure = implode('/', $settings->custom_permalink_structure) . '/';

        dump($customStructure, $this->getFormattedUrl($customStructure));

        //            $settings->custom_permalink_structure;

        // Use the same logic as `getFormattedUrl` to replace placeholders
        return $this->getFormattedUrl($customStructure);
    }
}
