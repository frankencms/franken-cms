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

            // Add description (always an excerpt) and content:encoded (full HTML when enabled)
            $excerpt = $this->getExcerpt($post);
            $xml .= '      <description><![CDATA[' . $excerpt . ']]></description>' . PHP_EOL;

            if ($includeFullText) {
                $content = $this->getPostContent($post);
                $xml .= '      <content:encoded><![CDATA[' . $content . ']]></content:encoded>' . PHP_EOL;
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

            // Add summary (always plain text excerpt) and content (full HTML when enabled)
            $excerpt = $this->getExcerpt($post);
            $xml .= '    <summary><![CDATA[' . $excerpt . ']]></summary>' . PHP_EOL;

            if ($includeFullText) {
                $content = $this->getPostContent($post);
                $xml .= '    <content type="html"><![CDATA[' . $content . ']]></content>' . PHP_EOL;
            }

            $xml .= '  </entry>' . PHP_EOL;
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Get excerpt for RSS description (plain text summary)
     */
    protected function getExcerpt(Post $post, int $length = 250): string
    {
        // Use post_teaser if available
        if (! empty($post->post_teaser)) {
            return strip_tags($post->post_teaser);
        }

        // Get the full content and strip HTML
        $content = $this->getPostContent($post);
        $plainText = strip_tags($content);

        // Remove extra whitespace
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        $plainText = trim($plainText);

        // Truncate to specified length
        if (mb_strlen($plainText) > $length) {
            $plainText = mb_substr($plainText, 0, $length);
            // Try to break at the last complete word
            $lastSpace = mb_strrpos($plainText, ' ');
            if ($lastSpace !== false) {
                $plainText = mb_substr($plainText, 0, $lastSpace);
            }
            $plainText .= '...';
        }

        return $plainText;
    }

    /**
     * Get post content (handle both string and array formats)
     * Converts TipTap JSON to HTML
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

        // If content is an array, convert TipTap JSON to HTML
        if (is_array($content)) {
            return $this->tiptapToHtml($content);
        }

        // Fallback: cast to string
        return (string) $content;
    }

    /**
     * Convert TipTap JSON structure to HTML
     */
    protected function tiptapToHtml(array $doc): string
    {
        if (! isset($doc['type']) || $doc['type'] !== 'doc' || ! isset($doc['content'])) {
            return '';
        }

        $html = '';

        foreach ($doc['content'] as $node) {
            $html .= $this->renderTiptapNode($node);
        }

        return $html;
    }

    /**
     * Render a single TipTap node to HTML
     */
    protected function renderTiptapNode(array $node): string
    {
        $type = $node['type'] ?? '';

        return match ($type) {
            'paragraph'      => $this->renderParagraph($node),
            'heading'        => $this->renderHeading($node),
            'bulletList'     => $this->renderBulletList($node),
            'orderedList'    => $this->renderOrderedList($node),
            'listItem'       => $this->renderListItem($node),
            'image'          => $this->renderImage($node),
            'codeBlock'      => $this->renderCodeBlock($node),
            'blockquote'     => $this->renderBlockquote($node),
            'horizontalRule' => '<hr>',
            'hardBreak'      => '<br>',
            default          => $this->renderGenericNode($node),
        };
    }

    protected function renderParagraph(array $node): string
    {
        $content = $this->renderContent($node['content'] ?? []);

        return $content ? "<p>{$content}</p>" : '';
    }

    protected function renderHeading(array $node): string
    {
        $level = $node['attrs']['level'] ?? 1;
        $content = $this->renderContent($node['content'] ?? []);

        return "<h{$level}>{$content}</h{$level}>";
    }

    protected function renderBulletList(array $node): string
    {
        $items = '';
        foreach ($node['content'] ?? [] as $item) {
            $items .= $this->renderTiptapNode($item);
        }

        return "<ul>{$items}</ul>";
    }

    protected function renderOrderedList(array $node): string
    {
        $items = '';
        foreach ($node['content'] ?? [] as $item) {
            $items .= $this->renderTiptapNode($item);
        }

        return "<ol>{$items}</ol>";
    }

    protected function renderListItem(array $node): string
    {
        $content = '';
        foreach ($node['content'] ?? [] as $item) {
            $content .= $this->renderTiptapNode($item);
        }

        return "<li>{$content}</li>";
    }

    protected function renderImage(array $node): string
    {
        $src = $node['attrs']['src'] ?? '';
        $alt = $node['attrs']['alt'] ?? '';
        $title = $node['attrs']['title'] ?? '';

        if (! $src) {
            return '';
        }

        $titleAttr = $title ? ' title="' . htmlspecialchars($title) . '"' : '';

        return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . "\"{$titleAttr}>";
    }

    protected function renderCodeBlock(array $node): string
    {
        $content = $this->renderContent($node['content'] ?? []);

        return "<pre><code>{$content}</code></pre>";
    }

    protected function renderBlockquote(array $node): string
    {
        $content = '';
        foreach ($node['content'] ?? [] as $item) {
            $content .= $this->renderTiptapNode($item);
        }

        return "<blockquote>{$content}</blockquote>";
    }

    protected function renderGenericNode(array $node): string
    {
        return $this->renderContent($node['content'] ?? []);
    }

    protected function renderContent(array $content): string
    {
        $html = '';

        foreach ($content as $item) {
            if (isset($item['type']) && $item['type'] === 'text') {
                $text = htmlspecialchars($item['text'] ?? '', ENT_NOQUOTES);
                $marks = $item['marks'] ?? [];

                // Apply text marks (bold, italic, etc.)
                foreach ($marks as $mark) {
                    $text = match ($mark['type'] ?? '') {
                        'bold'      => "<strong>{$text}</strong>",
                        'italic'    => "<em>{$text}</em>",
                        'underline' => "<u>{$text}</u>",
                        'strike'    => "<s>{$text}</s>",
                        'code'      => "<code>{$text}</code>",
                        'link'      => '<a href="' . htmlspecialchars($mark['attrs']['href'] ?? '') . "\">{$text}</a>",
                        default     => $text,
                    };
                }

                $html .= $text;
            } elseif (isset($item['type'])) {
                // Handle nested nodes
                $html .= $this->renderTiptapNode($item);
            }
        }

        return $html;
    }
}
