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
     * The site-wide fallback template, used when no type template, manual
     * upload, or default image resolves for a page
     */
    public static function defaultTemplate(): ?string
    {
        $view = config('franken-cms.og_image.default_template');

        return ($view && view()->exists($view)) ? $view : null;
    }

    /**
     * Whether the current page will carry an og-image component.
     * Mirrors the component's branch logic so AddSeoDefaults can defer
     * tag ownership to the Spatie middleware without duplicates.
     * Generation is scoped to CMS-managed pages (posts/pages resolved via
     * CurrentPageService); hand-coded routes with no current page always
     * keep the classic tag path, even when a site default og-default exists.
     */
    public static function resolvesFor(?Post $post): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        if (! $post) {
            return false;
        }

        // Summary-card posts keep the classic tag path (spatie always emits summary_large_image)
        if ($post->getMeta('seo_use_twitter_summary', app(SeoSettings::class)->use_twitter_summary_card)) {
            return false;
        }

        if (self::templateFor($post)) {
            return true;
        }

        if ($post->getFirstMedia('seo-og')) {
            return true;
        }

        if (SiteSettingsMedia::getInstance()->hasMedia('og-default')) {
            return true;
        }

        return self::defaultTemplate() !== null;
    }
}
