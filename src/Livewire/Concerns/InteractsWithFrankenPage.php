<?php

namespace FrankenCms\Livewire\Concerns;

use FrankenCms\Models\Post;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\View\Composers\FrankenFieldComposer;
use Illuminate\View\View;

/**
 * For theme templates written as Livewire page components.
 *
 * Receives the resolved CMS content ($page for pages, $post for posts) and,
 * on every render (including Livewire update requests), restores the current
 * page and shares $frankenFields so @franken* directives and frankenField()
 * work like they do in plain Blade templates.
 */
trait InteractsWithFrankenPage
{
    public ?Post $page = null;

    public ?Post $post = null;

    /**
     * The CMS content this component renders
     */
    public function frankenContent(): ?Post
    {
        return $this->page ?? $this->post;
    }

    public function renderingInteractsWithFrankenPage(View $view): void
    {
        $content = $this->frankenContent();

        if (! $content) {
            return;
        }

        app(CurrentPageService::class)->setPage($content);

        $template = $content->template ?: ($content->post_type === 'page' ? 'page' : 'post');
        $path = resource_path(sprintf('views/%s/%s.blade.php', config('franken-cms.theme_folder', 'theme'), $template));

        if (! file_exists($path)) {
            return;
        }

        $frankenFields = app(FrankenFieldComposer::class)->shareFieldsFor($path);

        if ($frankenFields !== null) {
            $view->with('frankenFields', $frankenFields);
        }
    }
}
