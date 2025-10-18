<?php

declare(strict_types=1);

namespace FrankenCms\Http\Middleware;

use Closure;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\SeoService;
use FrankenCms\Settings\SeoSettings;
use Illuminate\Http\Request;
use romanzipp\Seo\Structs\Link as LinkMeta;
use romanzipp\Seo\Structs\Meta;
use romanzipp\Seo\Structs\Meta\OpenGraph;
use romanzipp\Seo\Structs\Meta\Twitter;
use Symfony\Component\HttpFoundation\Response;

class AddSeoDefaults
{
    public function __construct(
        protected SeoSettings $settings,
        protected SeoService $seoService,
        protected CurrentPageService $currentPageService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Basic meta tags
        seo()->charset();
        seo()->viewport();
        seo()->csrfToken();

        // Get current post/page if available
        $post = $this->currentPageService->getPage();

        // Title & Description
        seo()->title($this->seoService->getTitle($post));
        seo()->description($this->seoService->getDescription($post));

        // Canonical URL
        if ($this->settings->enable_canonical) {
            seo()->canonical($this->seoService->getCanonicalUrl($post));
        }

        // Robots meta
        seo()->meta('robots', $this->seoService->getRobotsContent($post));

        // Theme color
        if ($themeColor = $this->seoService->getThemeColor()) {
            seo()->add(
                Meta::make()
                    ->name('theme-color')
                    ->content($themeColor)
            );
        }

        // Include favicon tags
        $this->includeFavicons();

        // Include OpenGraph tags
        $this->includeOpenGraph($post);

        // Include Twitter tags
        $this->includeTwitter($post);

        return $next($request);
    }

    /**
     * Add OpenGraph meta tags
     */
    private function includeOpenGraph($post = null): void
    {
        seo()->addMany([
            OpenGraph::make()
                ->property('locale')
                ->content(app()->getLocale()),

            OpenGraph::make()
                ->property('site_name')
                ->content($this->settings->site_name),

            OpenGraph::make()
                ->property('type')
                ->content($this->seoService->getOgType($post)),

            OpenGraph::make()
                ->property('url')
                ->content($this->seoService->getCanonicalUrl($post)),

            OpenGraph::make()
                ->property('title')
                ->content($this->seoService->getOgTitle($post)),
        ]);

        // Add OG description if available
        if ($description = $this->seoService->getOgDescription($post)) {
            seo()->add(
                OpenGraph::make()
                    ->property('description')
                    ->content($description)
            );
        }

        // Add OG image if available
        if ($image = $this->seoService->getOgImage($post)) {
            seo()->add(
                OpenGraph::make()
                    ->property('image')
                    ->content($image)
            );
        }

        // Add Facebook App ID if configured
        if ($this->settings->fb_app_id) {
            seo()->add(
                OpenGraph::make()
                    ->property('app_id')
                    ->content($this->settings->fb_app_id)
            );
        }
    }

    /**
     * Add Twitter meta tags
     */
    private function includeTwitter($post = null): void
    {
        // Check per-post setting first, then fall back to global setting
        $useTwitterSummary = $post
            ? $post->getMeta('seo_use_twitter_summary', $this->settings->use_twitter_summary_card)
            : $this->settings->use_twitter_summary_card;

        seo()->add(
            Twitter::make()
                ->name('card')
                ->content($useTwitterSummary ? 'summary' : 'summary_large_image')
        );

        // Add Twitter username if configured
        if ($this->settings->twitter_username && is_string($this->settings->twitter_username) && $this->settings->twitter_username !== '') {
            $username = $this->settings->twitter_username;
            // Ensure @ prefix
            if (! str_starts_with($username, '@')) {
                $username = "@{$username}";
            }

            seo()->add(
                Twitter::make()
                    ->name('site')
                    ->content($username)
            );
        }

        // Add Twitter title
        seo()->add(
            Twitter::make()
                ->name('title')
                ->content($this->seoService->getTwitterTitle($post))
        );

        // Add Twitter description if available
        if ($description = $this->seoService->getTwitterDescription($post)) {
            seo()->add(
                Twitter::make()
                    ->name('description')
                    ->content($description)
            );
        }

        // Add Twitter image if available
        if ($image = $this->seoService->getTwitterImage($post)) {
            seo()->add(
                Twitter::make()
                    ->name('image')
                    ->content($image)
            );
        }
    }

    /**
     * Add favicon link tags
     */
    private function includeFavicons(): void
    {
        // Apple Touch Icons
        $appleTouchSizes = [
            ['57x57', '57'],
            ['60x60', '60'],
            ['72x72', '72'],
            ['76x76', '76'],
            ['114x114', '114'],
            ['120x120', '120'],
            ['144x144', '144'],
            ['152x152', '152'],
        ];

        foreach ($appleTouchSizes as [$filename, $size]) {
            seo()->add(
                LinkMeta::make()
                    ->rel('apple-touch-icon')
                    ->attr('sizes', "{$size}x{$size}")
                    ->href("/apple-touch-icon-{$filename}.png")
            );
        }

        // Standard Favicons
        seo()->addMany([
            LinkMeta::make()
                ->rel('icon')
                ->attr('type', 'image/png')
                ->attr('sizes', '16x16')
                ->href('/favicon-16x16.png'),

            LinkMeta::make()
                ->rel('icon')
                ->attr('type', 'image/png')
                ->attr('sizes', '32x32')
                ->href('/favicon-32x32.png'),

            LinkMeta::make()
                ->rel('icon')
                ->attr('type', 'image/png')
                ->attr('sizes', '96x96')
                ->href('/favicon-96x96.png'),

            LinkMeta::make()
                ->rel('icon')
                ->attr('type', 'image/png')
                ->attr('sizes', '128x128')
                ->href('/favicon-128.png'),

            LinkMeta::make()
                ->rel('icon')
                ->attr('type', 'image/png')
                ->attr('sizes', '196x196')
                ->href('/favicon-196x196.png'),
        ]);

        // MS Tiles
        seo()->addMany([
            Meta::make()
                ->name('msapplication-TileColor')
                ->content('#ffffff'),

            Meta::make()
                ->name('msapplication-TileImage')
                ->content('/mstile-144x144.png'),

            Meta::make()
                ->name('msapplication-square70x70logo')
                ->content('/mstile-70x70.png'),

            Meta::make()
                ->name('msapplication-square150x150logo')
                ->content('/mstile-150x150.png'),

            Meta::make()
                ->name('msapplication-wide310x150logo')
                ->content('/mstile-310x150.png'),

            Meta::make()
                ->name('msapplication-square310x310logo')
                ->content('/mstile-310x310.png'),
        ]);
    }
}
