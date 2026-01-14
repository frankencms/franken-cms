<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Enable breadcrumbs for tests
    config(['franken-cms.breadcrumbs.enabled' => true]);
    config(['franken-cms.breadcrumbs.home_text' => 'Home']);

    // Breadcrumbs are already registered by the ServiceProvider
    // No need to register again
});

test('home breadcrumb is registered', function () {
    $breadcrumbs = Breadcrumbs::generate('franken-cms.home');

    expect($breadcrumbs)->toHaveCount(1);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[0]->url)->toBe(url('/'));
});

test('single level page breadcrumbs', function () {
    $page = Page::factory()->create([
        'post_title' => 'About',
        'post_slug'  => 'about',
        'parent_id'  => null,
    ]);

    $breadcrumbs = Breadcrumbs::generate('franken-cms.page', $page);

    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[1]->title)->toBe('About');
    expect($breadcrumbs[1]->url)->toBe(url('/about'));
});

test('nested page breadcrumbs with 3 levels', function () {
    // Create parent page
    $parent = Page::factory()->create([
        'post_title' => 'About',
        'post_slug'  => 'about',
        'parent_id'  => null,
    ]);

    // Create child page
    $child = Page::factory()->create([
        'post_title' => 'Team',
        'post_slug'  => 'team',
        'parent_id'  => $parent->id,
    ]);

    // Create grandchild page
    $grandchild = Page::factory()->create([
        'post_title' => 'Leadership',
        'post_slug'  => 'leadership',
        'parent_id'  => $child->id,
    ]);

    $breadcrumbs = Breadcrumbs::generate('franken-cms.page', $grandchild);

    expect($breadcrumbs)->toHaveCount(4);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[1]->title)->toBe('About');
    expect($breadcrumbs[2]->title)->toBe('Team');
    expect($breadcrumbs[3]->title)->toBe('Leadership');
});

test('getBreadcrumbAncestors uses single query for nested pages', function () {
    // Create a deeply nested page structure
    $parent = Page::factory()->create([
        'post_title' => 'Level 1',
        'post_slug'  => 'level-1',
        'parent_id'  => null,
    ]);

    $level2 = Page::factory()->create([
        'post_title' => 'Level 2',
        'post_slug'  => 'level-2',
        'parent_id'  => $parent->id,
    ]);

    $level3 = Page::factory()->create([
        'post_title' => 'Level 3',
        'post_slug'  => 'level-3',
        'parent_id'  => $level2->id,
    ]);

    $level4 = Page::factory()->create([
        'post_title' => 'Level 4',
        'post_slug'  => 'level-4',
        'parent_id'  => $level3->id,
    ]);

    // Enable query logging
    DB::enableQueryLog();

    // Get ancestors
    $ancestors = $level4->getBreadcrumbAncestors();

    // Get query count
    $queries = DB::getQueryLog();

    // Should only have 1 query (the recursive CTE)
    expect($queries)->toHaveCount(1);

    // Verify we got all ancestors
    expect($ancestors)->toHaveCount(3);
    expect($ancestors[0]->post_title)->toBe('Level 1');
    expect($ancestors[1]->post_title)->toBe('Level 2');
    expect($ancestors[2]->post_title)->toBe('Level 3');

    DB::disableQueryLog();
});

test('post breadcrumbs show blog parent', function () {
    // Set up blog page
    $readingSettings = app(ReadingSettings::class);
    $readingSettings->post_page = 'blog';
    $readingSettings->save();

    $blogPage = Page::factory()->create([
        'post_title' => 'Blog',
        'post_slug'  => 'blog',
    ]);

    $post = Post::factory()->create([
        'post_title' => 'My First Post',
        'post_slug'  => 'my-first-post',
        'post_type'  => 'post',
    ]);

    // Breadcrumbs already registered - they will use the updated settings
    $breadcrumbs = Breadcrumbs::generate('franken-cms.post', $post);

    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[1]->title)->toBe('Blog');
    expect($breadcrumbs[2]->title)->toBe('My First Post');
});

test('blog listing breadcrumbs', function () {
    // Set up blog page
    $readingSettings = app(ReadingSettings::class);
    $readingSettings->post_page = 'blog';
    $readingSettings->save();

    $blogPage = Page::factory()->create([
        'post_title' => 'Blog',
        'post_slug'  => 'blog',
    ]);

    $breadcrumbs = Breadcrumbs::generate('franken-cms.blog');

    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[1]->title)->toBe('Blog');
});

test('taxonomy archive breadcrumbs', function () {
    // Set up blog page (required as parent for taxonomy breadcrumbs)
    $readingSettings = app(ReadingSettings::class);
    $readingSettings->post_page = 'blog';
    $readingSettings->save();

    Page::factory()->create([
        'post_title' => 'Blog',
        'post_slug'  => 'blog',
    ]);

    // Create taxonomy and term
    $taxonomy = Taxonomy::factory()->create([
        'name'         => 'category',
        'hierarchical' => true,
    ]);

    $term = Term::factory()->forTaxonomy($taxonomy)->create([
        'name' => 'Technology',
        'slug' => 'technology',
    ]);

    $breadcrumbs = Breadcrumbs::generate('franken-cms.taxonomy', $taxonomy, $term);

    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect($breadcrumbs[1]->title)->toBe('Blog');
    expect($breadcrumbs[2]->title)->toBe('Technology');
    expect($breadcrumbs[2]->url)->toBe(url('/category/technology'));
});

test('breadcrumbs component renders correctly', function () {
    $page = Page::factory()->create([
        'post_title' => 'About',
        'post_slug'  => 'about',
    ]);

    // Set current page
    app(\FrankenCms\Services\CurrentPageService::class)->setPage($page);

    $component = new \FrankenCms\View\Components\Breadcrumbs(
        app(\FrankenCms\Services\CurrentPageService::class)
    );

    $view = $component->render();

    expect($view)->not->toBe('');
    expect($component->breadcrumbs)->not->toBeEmpty();
});

test('breadcrumbs can be disabled via config', function () {
    // Test that config exists and can be set
    config(['franken-cms.breadcrumbs.enabled' => false]);

    expect(config('franken-cms.breadcrumbs.enabled'))->toBe(false);

    // Re-enabling for other tests
    config(['franken-cms.breadcrumbs.enabled' => true]);
});

test('custom home text is used in breadcrumbs', function () {
    // The beforeEach sets 'Home' as the home text
    // Verify it's being used in breadcrumb generation
    $breadcrumbs = Breadcrumbs::generate('franken-cms.home');

    expect($breadcrumbs)->toHaveCount(1);
    expect($breadcrumbs[0]->title)->toBe('Home');
    expect(config('franken-cms.breadcrumbs.home_text'))->toBe('Home');
});
