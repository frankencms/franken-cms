<?php

namespace FrankenCms\View\Components;

use Daikazu\Breadcrumbs\Facades\Breadcrumbs as BreadcrumbsFacade;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\View\Component;
use Illuminate\View\View;

class Breadcrumbs extends Component
{
    public array $breadcrumbs = [];

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected CurrentPageService $pageService
    ) {
        $this->generateBreadcrumbs();
    }

    /**
     * Generate breadcrumbs for the current page
     */
    protected function generateBreadcrumbs(): void
    {
        $currentPage = $this->pageService->getPage();

        if (! $currentPage) {
            return;
        }

        // Don't show breadcrumbs on the homepage
        $readingSettings = app(ReadingSettings::class);
        if ($readingSettings->home_page === $currentPage->post_slug) {
            return;
        }

        // Determine the breadcrumb name based on the page type
        $breadcrumbName = match ($currentPage->post_type) {
            'page'  => 'franken-cms.page',
            'post'  => 'franken-cms.post',
            default => null,
        };

        if (! $breadcrumbName || ! BreadcrumbsFacade::has($breadcrumbName)) {
            return;
        }

        $this->breadcrumbs = BreadcrumbsFacade::generate($breadcrumbName, $currentPage)->toArray();
    }

    /**
     * Returning '' from render() would compile the empty string into a
     * zero-byte cached view that the framework rewrites on every request,
     * which sends the Vite dev server into an infinite full-reload loop.
     */
    public function shouldRender(): bool
    {
        return $this->breadcrumbs !== [];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('franken-cms::components.breadcrumbs');
    }
}
