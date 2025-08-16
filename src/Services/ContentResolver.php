<?php

namespace FrankenCms\Services;

use FrankenCMS\FrankenCms\Enums\PermalinkStructure;
use FrankenCMS\FrankenCms\Models\Page;
use FrankenCMS\FrankenCms\Models\Post;
use FrankenCMS\FrankenCms\Settings\CmsSettings;
use Illuminate\View\View;

readonly class ContentResolver
{
    public function __construct(
        private CmsSettings $settings
    ) {}

    public function resolveHomePage(): View
    {
        $homePage = $this->settings->home_page;
        if (! $homePage) {
            abort(404);
        }

        $page = Page::where('post_slug', $homePage)->firstOrFail();
        return TemplateResolver::resolve($page);
    }

    public function resolvePost(string $slug, ?string $queryId = null): ?Post
    {

        $post = match ($this->settings->permalink_structure) {
            PermalinkStructure::PLAIN->value => $this->findPostById($queryId),
            PermalinkStructure::DAY_AND_NAME->value,
            PermalinkStructure::MONTH_AND_NAME->value,
            PermalinkStructure::POST_NAME->value => $this->findPostBySlug($slug),
            PermalinkStructure::NUMERIC->value   => $this->findPostById($this->getLastSegment($slug)),
            PermalinkStructure::CUSTOM->value    => $this->findByCustomPermalink($slug),
            default                              => null,
        };

        if (! $post) {
            abort(404);
        }

        app(PostService::class)->setPost($post);

        return $post;

    }

    public function resolvePage(string $slug): ?View
    {
        $page = Page::where('post_slug', $slug)->firstOrFail();
        return TemplateResolver::resolve($page);
    }

    public function isPostPath(string $path): bool
    {
        $postPage = $this->settings->post_page;
        return $postPage && str_starts_with($path, $postPage);
    }

    public function extractSlugFromPostPath(string $path): string
    {
        return trim(str_replace($this->settings->post_page, '', $path), '/');
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
        $structure = $this->settings->custom_permalink_structure;
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
