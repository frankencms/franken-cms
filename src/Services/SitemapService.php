<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Carbon\Carbon;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
    /**
     * Cache key for sitemap index
     */
    protected const CACHE_KEY_INDEX = 'sitemap_index';

    /**
     * Cache key prefix for post type sitemaps
     */
    protected const CACHE_KEY_PREFIX = 'sitemap_';

    /**
     * Cache key for tracking cached post types
     */
    protected const CACHE_KEY_TYPES = 'sitemap_cached_types';

    public function __construct(
        protected SitemapSettings $settings
    ) {}

    /**
     * Check if sitemap generation is enabled
     */
    public function isEnabled(): bool
    {
        return $this->settings->enabled;
    }

    /**
     * Generate the sitemap index (always returns an index)
     */
    public function generate(): SitemapIndex
    {
        return Cache::rememberForever(self::CACHE_KEY_INDEX, function () {
            $sitemapIndex = SitemapIndex::create();

            // Always add pages sitemap
            $sitemapIndex->add(url('sitemap-pages.xml'));

            // Always add posts sitemap
            $sitemapIndex->add(url('sitemap-posts.xml'));

            // Add custom sitemaps
            foreach ($this->settings->custom_sitemaps as $customSitemap) {
                // Convert relative URLs to absolute
                if (str_starts_with($customSitemap, '/')) {
                    $customSitemap = url($customSitemap);
                }
                $sitemapIndex->add($customSitemap);
            }

            return $sitemapIndex;
        });
    }

    /**
     * Generate sitemap for a specific post type
     */
    public function generateForPostType(string $postType): Sitemap
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $postType;

        // Track this post type as cached
        $this->trackCachedPostType($postType);

        return Cache::rememberForever($cacheKey, function () use ($postType) {
            $posts = $this->getPostsForType($postType);
            $sitemap = Sitemap::create();

            foreach ($posts as $post) {
                $url = $this->createUrlForPost($post);
                $sitemap->add($url);
            }

            return $sitemap;
        });
    }

    /**
     * Write sitemap to file
     */
    public function writeToFile(string $filename = 'sitemap.xml'): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $sitemap = $this->generate();
        $sitemap->writeToFile(public_path($filename));
    }

    /**
     * Get sitemap as string
     */
    public function render(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        return $this->generate()->render();
    }

    /**
     * Clear all sitemap caches
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_INDEX);

        // Clear all tracked post type caches
        $cachedTypes = Cache::get(self::CACHE_KEY_TYPES, []);
        foreach ($cachedTypes as $postType) {
            Cache::forget(self::CACHE_KEY_PREFIX . $postType);
        }

        Cache::forget(self::CACHE_KEY_TYPES);
    }

    /**
     * Track a post type as having a cached sitemap
     */
    protected function trackCachedPostType(string $postType): void
    {
        $cachedTypes = Cache::get(self::CACHE_KEY_TYPES, []);
        if (! in_array($postType, $cachedTypes, true)) {
            $cachedTypes[] = $postType;
            Cache::forever(self::CACHE_KEY_TYPES, $cachedTypes);
        }
    }

    /**
     * Get posts for a specific post type
     */
    protected function getPostsForType(string $postType): Collection
    {
        // Get posts of the specified type
        $posts = Post::query()
            ->withoutGlobalScopes()
            ->with(['author', 'media', 'meta'])
            ->where('post_type', $postType)
            ->where('post_status', PostStatus::PUBLISH)
            ->where(function ($query) {
                $query->whereNull('post_published_at')
                    ->orWhere('post_published_at', '<=', now());
            })
            ->get();

        // Load all parent hierarchy at once for pages
        if ($postType === 'page') {
            $allParentIds = $this->getAllParentIds($posts);

            $allParents = collect();
            if ($allParentIds->isNotEmpty()) {
                $allParents = Post::query()
                    ->withoutGlobalScopes()
                    ->with(['author', 'media', 'meta'])
                    ->whereIn('id', $allParentIds)
                    ->get()
                    ->keyBy('id');
            }

            $this->attachParents($posts, $allParents);
            $this->attachParents($allParents, $allParents);
        }

        return $posts->filter(function (Post $post) {
            return ! $this->isExcluded($post);
        });
    }

    /**
     * Create a URL tag for a post
     */
    protected function createUrlForPost(Post $post): Url
    {
        $url = Url::create($this->getPostUrl($post))
            ->setLastModificationDate($post->updated_at ?? Carbon::now())
            ->setChangeFrequency($this->settings->default_change_frequency)
            ->setPriority($this->settings->default_priority);

        // Add images if enabled and post has featured image
        if ($this->settings->include_images && $post->hasMedia('featured')) {
            $image = $post->getFirstMedia('featured');
            if ($image) {
                $url->addImage($image->getFullUrl(), $post->post_title);
            }
        }

        return $url;
    }

    /**
     * Get all parent IDs recursively from a collection of posts using recursive CTE
     */
    protected function getAllParentIds(Collection $posts): Collection
    {
        $initialParentIds = $posts->pluck('parent_id')->filter()->unique()->values();

        if ($initialParentIds->isEmpty()) {
            return collect();
        }

        $placeholders = $initialParentIds->map(fn () => '?')->implode(',');
        $table = (new Post)->getTable();

        // Use recursive CTE to get all ancestors in a single query
        $results = DB::select("
            WITH RECURSIVE ancestors AS (
                SELECT id, parent_id
                FROM {$table}
                WHERE id IN ({$placeholders})

                UNION ALL

                SELECT p.id, p.parent_id
                FROM {$table} p
                INNER JOIN ancestors a ON p.id = a.parent_id
                WHERE a.parent_id IS NOT NULL
            )
            SELECT DISTINCT id FROM ancestors
        ", $initialParentIds->toArray());

        return collect($results)->pluck('id');
    }

    /**
     * Attach parent relationships to posts
     */
    protected function attachParents(Collection $posts, Collection $parents): void
    {
        foreach ($posts as $post) {
            if ($post->parent_id && isset($parents[$post->parent_id])) {
                $post->setRelation('parent', $parents[$post->parent_id]);
            } elseif ($post->parent_id === null) {
                $post->setRelation('parent', null);
            }
        }
    }

    /**
     * Get URL for a post (custom implementation to avoid lazy loading)
     */
    protected function getPostUrl(Post $post): string
    {
        // For pages, build hierarchical path without using the ancestors() method
        if ($post->post_type === 'page') {
            return $this->buildHierarchicalPath($post);
        }

        // For posts, use the normal URL accessor
        return $post->url;
    }

    /**
     * Build hierarchical URL for a page without triggering lazy loading
     */
    protected function buildHierarchicalPath(Post $post): string
    {
        $segments = [];
        $current = $post;

        // Traverse up the hierarchy using the loaded parent relations
        while ($current) {
            array_unshift($segments, $current->post_slug);

            // Check if parent relation is loaded before accessing it
            if ($current->relationLoaded('parent')) {
                $current = $current->parent;
            } else {
                break;
            }
        }

        return url(implode('/', $segments));
    }

    /**
     * Check if a post URL should be excluded from sitemap
     */
    protected function isExcluded(Post $post): bool
    {
        $url = $this->getPostUrl($post);

        foreach ($this->settings->excluded_paths as $excludedPath) {
            // Support wildcards - escape everything except *, then replace * with .*
            $pattern = str_replace('\\*', '.*', preg_quote($excludedPath, '/'));

            if (preg_match('/^' . $pattern . '$/', $url)) {
                return true;
            }
        }

        return false;
    }
}
