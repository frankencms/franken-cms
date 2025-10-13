<?php

namespace FrankenCms\Services;

use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Services\TemplateResolver;
use FrankenCms\Settings\PermalinkSettings;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\View\View;

readonly class ContentResolver
{
    public function __construct(
        private ReadingSettings $readingSettings,
        private PermalinkSettings $permalinkSettings
    ) {}

    public function resolveHomePage(): View
    {
        $homePage = $this->readingSettings->home_page;

        // If a specific homepage is set, use it
        if ($homePage) {
            $page = Page::where('post_slug', $homePage)->firstOrFail();
            return TemplateResolver::resolve($page);
        }

        // If no homepage is set, look for the theme's welcome/setup template
        $themeFolder = config('franken-cms.theme_folder');
        $welcomeView = sprintf('%s.welcome', $themeFolder);

        if (view()->exists($welcomeView)) {
            return view($welcomeView);
        }

        // If no welcome template exists, 404
        abort(404, 'No homepage configured. Please set a homepage in Settings > Reading.');
    }

    public function resolvePost(string $slug, ?string $queryId = null): ?Post
    {

        $post = match ($this->permalinkSettings->permalink_structure) {
            PermalinkStructure::POST_NAME->value      => $this->findPostBySlug($slug),
            PermalinkStructure::DAY_AND_NAME->value   => $this->findPostBySlug($this->getLastSegment($slug)),
            PermalinkStructure::MONTH_AND_NAME->value => $this->findPostBySlug($this->getLastSegment($slug)),
            PermalinkStructure::NUMERIC->value        => $this->findPostById($this->getLastSegment($slug)),
            PermalinkStructure::CUSTOM->value         => $this->findByCustomPermalink($slug),
            default                                   => null,
        };

        if (! $post) {
            abort(404);
        }

        app(PostService::class)->setPost($post);

        return $post;

    }

    public function resolvePage(string $slug): View
    {
        $page = Page::where('post_slug', $slug)->firstOrFail();

        return TemplateResolver::resolve($page);
    }

    public function isPostPath(string $path): bool
    {
        $postPage = $this->readingSettings->post_page;
        return $postPage && str_starts_with($path, $postPage);
    }

    public function extractSlugFromPostPath(string $path): string
    {
        return trim(str_replace($this->readingSettings->post_page, '', $path), '/');
    }

    private function findPostById(?string $id): ?Post
    {
        return $id ? Post::find($id) : null;
    }

    private function findPostBySlug(string $slug): ?Post
    {
        return Post::where('post_slug', $slug)->first();
    }

    private function findByCustomPermalink(string $slug): ?Post
    {
        $structure = $this->permalinkSettings->custom_permalink_structure;
        $segments = array_values(array_filter(explode('/', $slug)));

        // Return early if segments don't match structure length
        if (count($segments) !== count($structure)) {
            return null;
        }

        $query = Post::query();

        // Build query based on permalink structure
        foreach ($structure as $index => $structureTag) {
            match ($structureTag) {
                '%postname%' => $query->where('post_slug', $segments[$index]),
                '%post_id%'  => $query->where('id', $segments[$index]),
                default      => null
            };
        }

        return $query->first();
    }

    private function getLastSegment(string $path): string
    {
        $segments = explode('/', $path);
        return end($segments);
    }
}
