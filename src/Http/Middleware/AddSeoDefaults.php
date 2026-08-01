<?php

declare(strict_types=1);

namespace FrankenCms\Http\Middleware;

use Closure;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Exception;
use FrankenCms\Models\Post;
use FrankenCms\OgImage\OgImageFeature;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\FaviconGenerator;
use FrankenCms\Services\SeoService;
use FrankenCms\Settings\ReadingSettings;
use FrankenCms\Settings\SeoSettings;
use Illuminate\Http\Request;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;
use Laravel\Head\HeadBuilder;
use Symfony\Component\HttpFoundation\Response;

class AddSeoDefaults
{
    /**
     * Application-level default callbacks applied over the CMS defaults.
     *
     * @var array<int, callable(HeadBuilder): mixed>
     */
    protected static array $appDefaults = [];

    public function __construct(
        protected SeoSettings $settings,
        protected SeoService $seoService,
        protected CurrentPageService $currentPageService,
        protected FaviconGenerator $faviconGenerator,
    ) {}

    /**
     * Register application head defaults that override the CMS-settings
     * defaults field by field. Call from a service provider's boot method.
     *
     * @param  callable(HeadBuilder): mixed  $callback
     */
    public static function registering(callable $callback): void
    {
        static::$appDefaults[] = $callback;
    }

    public static function flushRegisteredCallbacks(): void
    {
        static::$appDefaults = [];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $post = $this->currentPageService->getPage();

        // When the og-image feature resolves for this page, the Spatie og-image
        // middleware owns og:image/twitter:image/twitter:card - don't duplicate them here.
        $ogImageHandledExternally = OgImageFeature::resolvesFor($post);

        $this->registerCmsDefaults();

        foreach (static::$appDefaults as $callback) {
            Head::defaults($callback);
        }

        $this->addPageMetadata($post, $ogImageHandledExternally);
        $this->addBreadcrumbSchema($post);

        return $next($request);
    }

    /**
     * Register the CMS-settings-driven defaults layer.
     *
     * This layer must render identically on every request regardless of the
     * og-image deferral outcome: Head::defaults() merges into a HeadManager
     * that can persist across requests under a worker runtime (Octane,
     * FrankenPHP), so a request-varying defaults layer would let a prior
     * request's fields (e.g. twitter:card) leak into a later, differently
     * shaped request. The twitter:card value itself is set per-request in
     * the runtime layer (see addPageMetadata()) instead.
     */
    protected function registerCmsDefaults(): void
    {
        Head::defaults(function (HeadBuilder $head): void {
            $separator = $this->settings->title_separator;
            $prepend = $this->settings->site_name_position === 'prepend';

            $head->title(
                $this->settings->site_name,
                prefix: $this->settings->append_site_name && $prepend
                    ? "{$this->settings->site_name} {$separator} "
                    : null,
                suffix: $this->settings->append_site_name && ! $prepend
                    ? " {$separator} {$this->settings->site_name}"
                    : null,
            );

            if ($this->settings->default_meta_description) {
                $head->description($this->settings->default_meta_description);
            }

            if ($this->settings->enable_canonical) {
                $head->canonical();
            }

            $head->robots("{$this->settings->default_robots_index}, {$this->settings->default_robots_follow}");

            $head->og(
                type: $this->settings->og_type ?: 'website',
                siteName: $this->settings->site_name,
                locale: app()->getLocale(),
            );

            if ($this->settings->fb_app_id) {
                $head->meta('og:app_id', $this->settings->fb_app_id);
            }

            // The card itself is set at runtime (see addPageMetadata()) so it
            // never depends on the og-image deferral outcome of a prior request
            // in this defaults layer. Registering the site handle here keeps
            // the twitter builder non-empty on non-deferral pages even before
            // the runtime layer runs, so twitter:title/description still derive.
            if ($handle = $this->twitterHandle()) {
                $head->twitter(site: $handle);
            }

            if ($themeColor = $this->seoService->getThemeColor()) {
                $head->themeColor($themeColor);
            }

            $this->addFaviconIcons($head);
        });
    }

    /**
     * Set runtime metadata for the resolved CMS page, mirroring the classic
     * tag ownership rules for the Spatie og-image middleware.
     */
    protected function addPageMetadata(?Post $post, bool $ogImageHandledExternally): void
    {
        if ($post) {
            Head::title($this->seoService->getTitle($post));

            if ($description = $this->seoService->getDescription($post)) {
                Head::description($description);
            }

            if ($this->settings->enable_canonical) {
                Head::canonical($this->seoService->getCanonicalUrl($post));
            }

            Head::robots($this->seoService->getRobotsContent($post));
        }

        Head::og(
            type: $this->seoService->getOgType($post),
            url: $this->seoService->getCanonicalUrl($post),
            // Only override og:title/og:description explicitly for a resolved
            // post, where seo_og_title/seo_og_description meta can differ from
            // the document title/description. Leave them null (unset) when
            // there's no post so laravel/head auto-derives them from the
            // document layer - including any app-registered override.
            title: $post ? $this->seoService->getOgTitle($post) : null,
            description: $post ? $this->seoService->getOgDescription($post) : null,
        );

        if ($ogImageHandledExternally) {
            return;
        }

        $useTwitterSummary = $post
            ? (bool) $post->getMeta('seo_use_twitter_summary', $this->settings->use_twitter_summary_card)
            : $this->settings->use_twitter_summary_card;

        Head::twitter(card: $useTwitterSummary ? TwitterCard::Summary : TwitterCard::SummaryWithLargeImage);

        if ($image = $this->seoService->getOgImage($post)) {
            Head::ogImage($image);
        }

        if ($image = $this->seoService->getTwitterImage($post)) {
            Head::twitterImage($image);
        }
    }

    /**
     * Emit icon tags for generated favicon files that exist on disk.
     */
    protected function addFaviconIcons(HeadBuilder $head): void
    {
        foreach ($this->faviconGenerator->generatedFiles() as $filename => $sizes) {
            if (str_starts_with($filename, 'apple-touch-icon')) {
                $head->appleTouchIcon("/{$filename}", sizes: $sizes);

                continue;
            }

            $head->icon(
                "/{$filename}",
                type: $filename === 'favicon.ico' ? 'image/x-icon' : 'image/png',
                sizes: $sizes,
            );
        }
    }

    /**
     * Add JSON-LD breadcrumb structured data for CMS pages.
     */
    protected function addBreadcrumbSchema(?Post $post): void
    {
        if (! $post) {
            return;
        }

        if (app(ReadingSettings::class)->home_page === $post->post_slug) {
            return;
        }

        $breadcrumbName = match ($post->post_type) {
            'page'  => 'franken-cms.page',
            'post'  => 'franken-cms.post',
            default => null,
        };

        if (! $breadcrumbName) {
            return;
        }

        try {
            $breadcrumbs = Breadcrumbs::generate($breadcrumbName, $post);

            $schema = Schema::breadcrumbs();

            foreach ($breadcrumbs as $breadcrumb) {
                $schema->item($breadcrumb->title, $breadcrumb->url ?: request()->fullUrl());
            }

            Head::schema($schema);
        } catch (Exception) {
            // Breadcrumbs are best-effort; skip when generation fails.
        }
    }

    /**
     * The configured Twitter/X handle, normalized to include the @ prefix.
     */
    protected function twitterHandle(): ?string
    {
        $username = $this->settings->twitter_username;

        if (! is_string($username) || $username === '') {
            return null;
        }

        return str_starts_with($username, '@') ? $username : "@{$username}";
    }
}
