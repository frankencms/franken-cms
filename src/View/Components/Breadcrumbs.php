<?php

namespace FrankenCms\View\Components;

use Diglactic\Breadcrumbs\Breadcrumbs as BreadcrumbsFacade;
use Exception;
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

        if (! $breadcrumbName) {
            return;
        }

        try {
            // Generate breadcrumbs using the diglactic package
            $this->breadcrumbs = BreadcrumbsFacade::generate($breadcrumbName, $currentPage)->toArray();
        } catch (Exception $e) {
            // Silently fail if breadcrumbs can't be generated
            // This allows pages without breadcrumb definitions to work normally
            $this->breadcrumbs = [];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View | string
    {
        // Don't render if no breadcrumbs
        if (empty($this->breadcrumbs)) {
            return '';
        }

        return view('franken-cms::components.breadcrumbs');
    }
}
