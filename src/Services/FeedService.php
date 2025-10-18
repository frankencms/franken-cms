<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FeedService
{
    /**
     * Cache key for RSS feed
     */
    protected const CACHE_KEY_RSS = 'feed_rss';

    /**
     * Cache key for Atom feed
     */
    protected const CACHE_KEY_ATOM = 'feed_atom';

    public function __construct(
        protected ReadingSettings $readingSettings,
        protected GeneralSettings $generalSettings
    ) {}

    /**
     * Check if feeds are enabled
     */
    public function isEnabled(): bool
    {
        return $this->readingSettings->enable_feeds;
    }

    /**
     * Generate RSS 2.0 feed
     */
    public function generateRss(): string
    {
        return Cache::rememberForever(self::CACHE_KEY_RSS, function () {
            return $this->buildRssFeed();
        });
    }

    /**
     * Generate Atom 1.0 feed
     */
    public function generateAtom(): string
    {
        return Cache::rememberForever(self::CACHE_KEY_ATOM, function () {
            return $this->buildAtomFeed();
        });
    }

    /**
     * Clear all feed caches
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_RSS);
        Cache::forget(self::CACHE_KEY_ATOM);
    }

    /**
     * Convert a datetime to the configured timezone
     */
    protected function toTimezone($datetime)
    {
        if ($datetime === null) {
            return null;
        }

        $timezone = $this->generalSettings->timezone ?? 'UTC';

        return $datetime->copy()->setTimezone($timezone);
    }

    /**
     * Get posts for feed
     */
    protected function getPosts(): Collection
    {
        $limit = $this->readingSettings->syndicate_feeds ?? 10;

        return Post::query()
            ->withoutGlobalScopes()
            ->with(['author', 'media', 'categories', 'tags'])
            ->where('post_type', 'post')
            ->where('post_status', PostStatus::PUBLISH)
            ->where(function ($query) {
                $query->whereNull('post_published_at')
                    ->orWhere('post_published_at', '<=', now());
            })
            ->orderBy('post_published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Build RSS 2.0 feed
     */
    protected function buildRssFeed(): string
    {
        $posts = $this->getPosts();
        $siteTitle = $this->generalSettings->title;
        $siteUrl = config('app.url');
        $includeFullText = $this->readingSettings->include_in_feed === 'full_text';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
        $xml .= '  <channel>' . PHP_EOL;
        $xml .= '    <title>' . htmlspecialchars($siteTitle) . '</title>' . PHP_EOL;
        $xml .= '    <link>' . htmlspecialchars($siteUrl) . '</link>' . PHP_EOL;
        $xml .= '    <description>' . htmlspecialchars($siteTitle) . '</description>' . PHP_EOL;
        $xml .= '    <language>en</language>' . PHP_EOL;
        $xml .= '    <lastBuildDate>' . $this->toTimezone(now())->toRssString() . '</lastBuildDate>' . PHP_EOL;
        $xml .= '    <atom:link href="' . htmlspecialchars(url('/feed')) . '" rel="self" type="application/rss+xml" />' . PHP_EOL;

        foreach ($posts as $post) {
            $postUrl = url($post->url);

            $xml .= '    <item>' . PHP_EOL;
            $xml .= '      <title>' . htmlspecialchars($post->post_title) . '</title>' . PHP_EOL;
            $xml .= '      <link>' . htmlspecialchars($postUrl) . '</link>' . PHP_EOL;
            $xml .= '      <guid isPermaLink="true">' . htmlspecialchars($postUrl) . '</guid>' . PHP_EOL;

            $pubDate = $this->toTimezone($post->post_published_at) ?? $this->toTimezone(now());
            $xml .= '      <pubDate>' . $pubDate->toRssString() . '</pubDate>' . PHP_EOL;

            if ($post->author) {
                $xml .= '      <dc:creator>' . htmlspecialchars($post->author->name) . '</dc:creator>' . PHP_EOL;
            }

            // Add categories
            foreach ($post->categories as $category) {
                $xml .= '      <category>' . htmlspecialchars($category->name) . '</category>' . PHP_EOL;
            }

            // Add description/content
            if ($includeFullText) {
                $content = $this->getPostContent($post);
                $xml .= '      <description><![CDATA[' . $content . ']]></description>' . PHP_EOL;
                $xml .= '      <content:encoded><![CDATA[' . $content . ']]></content:encoded>' . PHP_EOL;
            } else {
                $excerpt = $post->post_teaser ?? strip_tags($this->getPostContent($post));
                $xml .= '      <description><![CDATA[' . $excerpt . ']]></description>' . PHP_EOL;
            }

            $xml .= '    </item>' . PHP_EOL;
        }

        $xml .= '  </channel>' . PHP_EOL;
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Build Atom 1.0 feed
     */
    protected function buildAtomFeed(): string
    {
        $posts = $this->getPosts();
        $siteTitle = $this->generalSettings->title;
        $siteUrl = config('app.url');
        $includeFullText = $this->readingSettings->include_in_feed === 'full_text';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . PHP_EOL;
        $xml .= '  <title>' . htmlspecialchars($siteTitle) . '</title>' . PHP_EOL;
        $xml .= '  <link href="' . htmlspecialchars($siteUrl) . '" />' . PHP_EOL;
        $xml .= '  <link href="' . htmlspecialchars(url('/feed/atom')) . '" rel="self" />' . PHP_EOL;
        $xml .= '  <id>' . htmlspecialchars($siteUrl) . '</id>' . PHP_EOL;
        $xml .= '  <updated>' . $this->toTimezone(now())->toAtomString() . '</updated>' . PHP_EOL;

        foreach ($posts as $post) {
            $postUrl = url($post->url);

            $xml .= '  <entry>' . PHP_EOL;
            $xml .= '    <title>' . htmlspecialchars($post->post_title) . '</title>' . PHP_EOL;
            $xml .= '    <link href="' . htmlspecialchars($postUrl) . '" />' . PHP_EOL;
            $xml .= '    <id>' . htmlspecialchars($postUrl) . '</id>' . PHP_EOL;

            $updated = $this->toTimezone($post->updated_at) ?? $this->toTimezone(now());
            $published = $this->toTimezone($post->post_published_at) ?? $this->toTimezone(now());

            $xml .= '    <updated>' . $updated->toAtomString() . '</updated>' . PHP_EOL;
            $xml .= '    <published>' . $published->toAtomString() . '</published>' . PHP_EOL;

            if ($post->author) {
                $xml .= '    <author><name>' . htmlspecialchars($post->author->name) . '</name></author>' . PHP_EOL;
            }

            // Add categories
            foreach ($post->categories as $category) {
                $xml .= '    <category term="' . htmlspecialchars($category->slug) . '" label="' . htmlspecialchars($category->name) . '" />' . PHP_EOL;
            }

            // Add content
            if ($includeFullText) {
                $content = $this->getPostContent($post);
                $xml .= '    <content type="html"><![CDATA[' . $content . ']]></content>' . PHP_EOL;
            } else {
                $excerpt = $post->post_teaser ?? strip_tags($this->getPostContent($post));
                $xml .= '    <summary><![CDATA[' . $excerpt . ']]></summary>' . PHP_EOL;
            }

            $xml .= '  </entry>' . PHP_EOL;
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Get post content (handle both string and array formats)
     */
    protected function getPostContent(Post $post): string
    {
        $content = $post->post_content;

        // If content is null, return empty string
        if ($content === null) {
            return '';
        }

        // If content is already a string, return it
        if (is_string($content)) {
            return $content;
        }

        // If content is an array, try to extract the content field
        if (is_array($content)) {
            // Handle nested content structure
            if (isset($content['content'])) {
                return is_string($content['content']) ? $content['content'] : '';
            }

            // If no 'content' key, try to convert the array to a string
            // This handles cases where the array might be structured differently
            return '';
        }

        // Fallback: cast to string
        return (string) $content;
    }
}
