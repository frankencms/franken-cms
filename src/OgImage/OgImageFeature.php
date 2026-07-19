<?php

namespace FrankenCms\OgImage;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\SeoSettings;
use Spatie\OgImage\OgImageServiceProvider;

class OgImageFeature
{
    /**
     * Check if spatie/laravel-og-image is installed
     */
    public static function isInstalled(): bool
    {
        return class_exists(OgImageServiceProvider::class);
    }

    /**
     * Installed and enabled in config
     */
    public static function isEnabled(): bool
    {
        return self::isInstalled() && (bool) config('franken-cms.og_image.enabled', true);
    }

    /**
     * The Blade view mapped to this post's type, if it exists
     */
    public static function templateFor(?Post $post): ?string
    {
        if (! $post) {
            return null;
        }

        $view = config("franken-cms.og_image.templates.{$post->post_type}");

        return ($view && view()->exists($view)) ? $view : null;
    }

    /**
     * Whether the current page will carry an og-image component.
     * Mirrors the component's branch logic so AddSeoDefaults can defer
     * tag ownership to the Spatie middleware without duplicates.
     */
    public static function resolvesFor(?Post $post): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        // Summary-card posts keep the classic tag path (spatie always emits summary_large_image)
        $usesSummary = $post
            ? $post->getMeta('seo_use_twitter_summary', app(SeoSettings::class)->use_twitter_summary_card)
            : app(SeoSettings::class)->use_twitter_summary_card;

        if ($usesSummary) {
            return false;
        }

        if (self::templateFor($post)) {
            return true;
        }

        if ($post?->getFirstMedia('seo-og')) {
            return true;
        }

        return SiteSettingsMedia::getInstance()->hasMedia('og-default');
    }
}
