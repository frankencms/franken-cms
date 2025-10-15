<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Page;
use FrankenCms\Settings\ReadingSettings;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Ensure clean state
    Config::set('franken-cms.theme_folder', 'theme');

    // Clear any existing pages
    Page::query()->delete();
});

describe('Single-level page routing', function () {
    test('can access published single-level page', function () {
        $page = Page::factory()->create([
            'post_slug' => 'about',
            'post_title' => 'About Us',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $response = $this->get('/about');

        $response->assertStatus(200);
    });

    test('cannot access draft single-level page', function () {
        $page = Page::factory()->create([
            'post_slug' => 'about',
            'post_title' => 'About Us',
            'post_status' => PostStatus::DRAFT,
            'parent_id' => null,
        ]);

        $response = $this->get('/about');

        $response->assertStatus(404);
    });

    test('generates correct URL for single-level page', function () {
        $page = Page::factory()->create([
            'post_slug' => 'contact',
            'post_title' => 'Contact',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        expect($page->url)->toBe('/contact');
    });
});

describe('Two-level nested page routing', function () {
    test('can access published parent page', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $response = $this->get('/company');

        $response->assertStatus(200);
    });

    test('can access published child page', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'post_title' => 'Our Team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $parent->id,
        ]);

        $response = $this->get('/company/team');

        $response->assertStatus(200);
    });

    test('generates correct URL for child page', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'post_title' => 'Our Team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $parent->id,
        ]);

        expect($child->url)->toBe('/company/team');
    });

    test('cannot access draft child page even if parent is published', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'post_title' => 'Our Team',
            'post_status' => PostStatus::DRAFT,
            'parent_id' => $parent->id,
        ]);

        $response = $this->get('/company/team');

        $response->assertStatus(404);
    });
});

describe('Three-level nested page routing', function () {
    test('can access all three levels of published pages', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'team',
            'post_title' => 'Our Team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'leadership',
            'post_title' => 'Leadership',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level2->id,
        ]);

        // Test all three levels
        $this->get('/company')->assertStatus(200);
        $this->get('/company/team')->assertStatus(200);
        $this->get('/company/team/leadership')->assertStatus(200);
    });

    test('generates correct URL for deeply nested page', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'team',
            'post_title' => 'Our Team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'leadership',
            'post_title' => 'Leadership',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level2->id,
        ]);

        expect($level3->url)->toBe('/company/team/leadership');
    });
});

describe('Four-level nested page routing', function () {
    test('can access all four levels of published pages', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'post_title' => 'Company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'departments',
            'post_title' => 'Departments',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'engineering',
            'post_title' => 'Engineering',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level2->id,
        ]);

        $level4 = Page::factory()->create([
            'post_slug' => 'backend',
            'post_title' => 'Backend Team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $level3->id,
        ]);

        // Test all four levels
        $this->get('/company')->assertStatus(200);
        $this->get('/company/departments')->assertStatus(200);
        $this->get('/company/departments/engineering')->assertStatus(200);
        $this->get('/company/departments/engineering/backend')->assertStatus(200);
    });

    test('generates correct URL for four-level nested page', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'departments',
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'engineering',
            'parent_id' => $level2->id,
        ]);

        $level4 = Page::factory()->create([
            'post_slug' => 'backend',
            'parent_id' => $level3->id,
        ]);

        expect($level4->url)->toBe('/company/departments/engineering/backend');
    });
});

describe('Page hierarchy edge cases', function () {
    test('returns 404 for non-existent page', function () {
        $response = $this->get('/does-not-exist');

        $response->assertStatus(404);
    });

    test('returns 404 for non-existent nested page', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $response = $this->get('/company/does-not-exist');

        $response->assertStatus(404);
    });

    test('returns 404 for incorrect hierarchy path', function () {
        $parent1 = Page::factory()->create([
            'post_slug' => 'company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $parent2 = Page::factory()->create([
            'post_slug' => 'about',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $parent1->id, // Actually belongs to 'company'
        ]);

        // Should work
        $this->get('/company/team')->assertStatus(200);

        // Should 404 because team is not a child of about
        $this->get('/about/team')->assertStatus(404);
    });

    test('handles pages with same slug but different parents', function () {
        $company = Page::factory()->create([
            'post_slug' => 'company',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        $products = Page::factory()->create([
            'post_slug' => 'products',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => null,
        ]);

        // Both have a child with slug 'overview'
        $companyOverview = Page::factory()->create([
            'post_slug' => 'overview',
            'post_title' => 'Company Overview',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $company->id,
        ]);

        $productsOverview = Page::factory()->create([
            'post_slug' => 'overview',
            'post_title' => 'Products Overview',
            'post_status' => PostStatus::PUBLISH,
            'parent_id' => $products->id,
        ]);

        // Both should work and return different pages
        $response1 = $this->get('/company/overview');
        $response2 = $this->get('/products/overview');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    });
});

describe('Page ancestors method', function () {
    test('single-level page has no ancestors', function () {
        $page = Page::factory()->create([
            'post_slug' => 'about',
            'parent_id' => null,
        ]);

        expect($page->ancestors())->toHaveCount(0);
    });

    test('two-level page has one ancestor', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'parent_id' => $parent->id,
        ]);

        $ancestors = $child->ancestors();

        expect($ancestors)->toHaveCount(1);
        expect($ancestors->first()->post_slug)->toBe('company');
    });

    test('three-level page has two ancestors in correct order', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'team',
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'leadership',
            'parent_id' => $level2->id,
        ]);

        $ancestors = $level3->ancestors();

        expect($ancestors)->toHaveCount(2);
        expect($ancestors->pluck('post_slug')->toArray())->toBe(['company', 'team']);
    });
});

describe('Page hierarchical path method', function () {
    test('generates correct path for single-level page', function () {
        $page = Page::factory()->create([
            'post_slug' => 'about',
            'parent_id' => null,
        ]);

        expect($page->getHierarchicalPath())->toBe('/about');
    });

    test('generates correct path for two-level page', function () {
        $parent = Page::factory()->create([
            'post_slug' => 'company',
            'parent_id' => null,
        ]);

        $child = Page::factory()->create([
            'post_slug' => 'team',
            'parent_id' => $parent->id,
        ]);

        expect($child->getHierarchicalPath())->toBe('/company/team');
    });

    test('generates correct path for three-level page', function () {
        $level1 = Page::factory()->create([
            'post_slug' => 'company',
            'parent_id' => null,
        ]);

        $level2 = Page::factory()->create([
            'post_slug' => 'team',
            'parent_id' => $level1->id,
        ]);

        $level3 = Page::factory()->create([
            'post_slug' => 'leadership',
            'parent_id' => $level2->id,
        ]);

        expect($level3->getHierarchicalPath())->toBe('/company/team/leadership');
    });
});
