<?php

namespace FrankenCms\Services;

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Manager;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use FrankenCms\Settings\ReadingSettings;
use ReflectionClass;

class BreadcrumbService
{
    public function __construct(
        private readonly ReadingSettings $readingSettings
    ) {}

    /**
     * Register all automatic breadcrumbs for FrankenCMS content
     */
    public function registerBreadcrumbs(): void
    {
        $this->registerHomeBreadcrumb();
        $this->registerPageBreadcrumbs();
        $this->registerBlogBreadcrumbs();
        $this->registerPostBreadcrumbs();
        $this->registerTaxonomyBreadcrumbs();
    }

    /**
     * Register homepage breadcrumb
     */
    protected function registerHomeBreadcrumb(): void
    {
        if ($this->isBreadcrumbRegistered('franken-cms.home')) {
            return;
        }

        Breadcrumbs::for('franken-cms.home', function ($trail) {
            $homeText = config('franken-cms.breadcrumbs.home_text', 'Home');
            $trail->push($homeText, url('/'));
        });
    }

    /**
     * Register breadcrumbs for pages
     */
    protected function registerPageBreadcrumbs(): void
    {
        if ($this->isBreadcrumbRegistered('franken-cms.page')) {
            return;
        }

        Breadcrumbs::for('franken-cms.page', function ($trail, Page $page) {
            // Add home
            $trail->parent('franken-cms.home');

            // Get ancestors efficiently using recursive CTE
            $ancestors = $page->getBreadcrumbAncestors();

            // Add each ancestor to the trail
            foreach ($ancestors as $ancestor) {
                // Build the URL by getting the hierarchical path for this ancestor
                $ancestorPage = Page::withoutGlobalScopes()->find($ancestor->id);
                $url = url($ancestorPage->getHierarchicalPath());
                $trail->push($ancestor->post_title, $url);
            }

            // Add current page
            $trail->push($page->post_title, url($page->getHierarchicalPath()));
        });
    }

    /**
     * Register breadcrumbs for blog listing page
     */
    protected function registerBlogBreadcrumbs(): void
    {
        if ($this->isBreadcrumbRegistered('franken-cms.blog')) {
            return;
        }

        Breadcrumbs::for('franken-cms.blog', function ($trail) {
            // Add home
            $trail->parent('franken-cms.home');

            // Get the blog page
            if ($this->readingSettings->post_page) {
                $blogPage = Page::where('post_slug', $this->readingSettings->post_page)->first();
                if ($blogPage) {
                    $trail->push($blogPage->post_title, url('/' . $this->readingSettings->post_page));
                }
            }
        });
    }

    /**
     * Register breadcrumbs for individual posts
     */
    protected function registerPostBreadcrumbs(): void
    {
        if ($this->isBreadcrumbRegistered('franken-cms.post')) {
            return;
        }

        Breadcrumbs::for('franken-cms.post', function ($trail, Post $post) {
            // Add blog listing as parent
            $trail->parent('franken-cms.blog');

            // Add current post
            $trail->push($post->post_title);
        });
    }

    /**
     * Register breadcrumbs for taxonomy archives (categories, tags, etc.)
     */
    protected function registerTaxonomyBreadcrumbs(): void
    {
        if ($this->isBreadcrumbRegistered('franken-cms.taxonomy')) {
            return;
        }

        Breadcrumbs::for('franken-cms.taxonomy', function ($trail, Taxonomy $taxonomy, Term $term) {
            // Add blog listing as parent
            $trail->parent('franken-cms.blog');

            // Add taxonomy archive
            $trail->push($term->name, url('/' . $taxonomy->name . '/' . $term->slug));
        });
    }

    /**
     * Load user-defined breadcrumbs from routes/breadcrumbs.php if it exists
     */
    public function loadUserBreadcrumbs(): void
    {
        $userBreadcrumbsFile = base_path('routes/breadcrumbs.php');

        if (file_exists($userBreadcrumbsFile)) {
            // Use require_once to prevent loading the file multiple times
            // which would cause duplicate breadcrumb registration errors
            require_once $userBreadcrumbsFile;
        }
    }

    /**
     * Check if a breadcrumb is already registered
     */
    protected function isBreadcrumbRegistered(string $name): bool
    {
        $manager = app(Manager::class);
        $reflection = new ReflectionClass($manager);
        $callbacksProperty = $reflection->getProperty('callbacks');
        $callbacksProperty->setAccessible(true);
        $callbacks = $callbacksProperty->getValue($manager);

        return isset($callbacks[$name]);
    }
}
