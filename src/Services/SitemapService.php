<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Carbon\Carbon;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Collection;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SitemapService
{
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
    }

    /**
     * Generate sitemap for a specific post type
     */
    public function generateForPostType(string $postType): Sitemap
    {
        $posts = $this->getPostsForType($postType);
        $sitemap = Sitemap::create();

        foreach ($posts as $post) {
            $url = $this->createUrlForPost($post);
            $sitemap->add($url);
        }

        return $sitemap;
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
     * Get posts to include in sitemap
     */
    protected function getIncludedPosts(): Collection
    {
        // First, get the posts we want to include (always posts and pages)
        $posts = Post::query()
            ->withoutGlobalScopes()
            ->with(['author', 'media', 'meta'])
            ->whereIn('post_type', ['post', 'page'])
            ->where('post_status', PostStatus::PUBLISH)
            ->where(function ($query) {
                $query->whereNull('post_published_at')
                    ->orWhere('post_published_at', '<=', now());
            })
            ->get();

        // Load all parent hierarchy at once
        // Get all unique parent IDs from the posts
        $allParentIds = $this->getAllParentIds($posts);

        // Load all parents in a single query
        $allParents = collect();
        if ($allParentIds->isNotEmpty()) {
            $allParents = Post::query()
                ->withoutGlobalScopes()
                ->with(['author', 'media', 'meta'])
                ->whereIn('id', $allParentIds)
                ->get()
                ->keyBy('id');
        }

        // Manually set parent relationships using the loaded parents
        $this->attachParents($posts, $allParents);

        // Also attach parents to parents (for nested hierarchies)
        $this->attachParents($allParents, $allParents);

        return $posts->filter(function (Post $post) {
            return ! $this->isExcluded($post);
        });
    }

    /**
     * Get all parent IDs recursively from a collection of posts
     */
    protected function getAllParentIds(Collection $posts): Collection
    {
        $parentIds = collect();
        $toProcess = $posts;

        while ($toProcess->isNotEmpty()) {
            $currentParentIds = $toProcess->pluck('parent_id')->filter();
            if ($currentParentIds->isEmpty()) {
                break;
            }

            $parentIds = $parentIds->merge($currentParentIds);

            // Load the next level of parents to get their parent_ids
            $toProcess = Post::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $currentParentIds)
                ->get(['id', 'parent_id']);
        }

        return $parentIds->unique();
    }

    /**
     * Attach parent relationships to posts
     */
    protected function attachParents(Collection $posts, Collection $parents): void
    {
        foreach ($posts as $post) {
            if ($post->parent_id && isset($parents[$post->parent_id])) {
                // Use setRelation to manually set the loaded parent
                // This prevents lazy loading when accessing the parent later
                $parent = $parents[$post->parent_id];
                $post->setRelation('parent', $parent);

                // Also mark the relation as loaded to prevent Laravel from trying to lazy load
                // This is needed because of the HasMeta trait's getAttribute() override
                $post->relationLoaded('parent'); // Just check if it's loaded
            } else if ($post->parent_id === null) {
                // Set parent to null explicitly to prevent lazy loading attempts
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
     * Build hierarchical path for a page without triggering lazy loading
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
                // Parent not loaded, stop traversal
                break;
            }
        }

        return '/' . implode('/', $segments);
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
}
