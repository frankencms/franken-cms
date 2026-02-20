<?php

use FrankenCms\Models\Post;
use FrankenCms\Services\FeedService;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\ReadingSettings;
use FrankenCms\Tests\Support\User;
use Illuminate\Support\Facades\Cache;

/**
 * Create a mock ReadingSettings with proper initialization.
 */
function createReadingSettingsMock(): ReadingSettings
{
    $settings = Mockery::mock(ReadingSettings::class)->makePartial();

    $reflection = new ReflectionProperty(\Spatie\LaravelSettings\Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    $settings->enable_feeds = true;
    $settings->syndicate_feeds = 10;
    $settings->include_in_feed = 'full_text';
    $settings->home_page = null;
    $settings->post_page = 'blog';
    $settings->posts_per_page = 10;

    return $settings;
}

/**
 * Create a mock GeneralSettings with proper initialization.
 */
function createGeneralSettingsMock(): GeneralSettings
{
    $settings = Mockery::mock(GeneralSettings::class)->makePartial();

    $reflection = new ReflectionProperty(\Spatie\LaravelSettings\Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    $settings->title = 'Test Site';
    $settings->timezone = 'UTC';

    return $settings;
}

beforeEach(function () {
    User::create([
        'id'       => 1,
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    config()->set('franken-cms.reading_settings', [
        'post_page' => 'blog',
    ]);

    Cache::forget('feed_rss');
    Cache::forget('feed_atom');

    $this->readingSettings = createReadingSettingsMock();
    $this->generalSettings = createGeneralSettingsMock();
    $this->feedService = new FeedService($this->readingSettings, $this->generalSettings);
});

afterEach(function () {
    Post::query()->delete();

    Cache::forget('feed_rss');
    Cache::forget('feed_atom');
});

describe('CDATA escaping', function () {
    it('escapes CDATA-breaking sequences in RSS feed content', function () {
        Post::factory()->create([
            'post_title'        => 'CDATA Test',
            'post_teaser'       => 'Break out: ]]> and inject',
            'post_published_at' => now()->subDay(),
        ]);

        $feed = $this->feedService->generateRss();

        // Must not contain raw ]]> inside a CDATA block (except the closing one)
        // The escaped version splits it: ]]]]><![CDATA[>
        expect($feed)->not->toContain('<![CDATA[Break out: ]]> and inject]]>');
        expect($feed)->toContain('Break out: ]]]]><![CDATA[> and inject');
    });

    it('escapes CDATA-breaking sequences in Atom feed content', function () {
        Post::factory()->create([
            'post_title'        => 'CDATA Atom Test',
            'post_teaser'       => 'Break out: ]]> and inject',
            'post_published_at' => now()->subDay(),
        ]);

        $feed = $this->feedService->generateAtom();

        expect($feed)->not->toContain('<![CDATA[Break out: ]]> and inject]]>');
        expect($feed)->toContain('Break out: ]]]]><![CDATA[> and inject');
    });

    it('escapes CDATA-breaking sequences in RSS full content', function () {
        Post::factory()->create([
            'post_title'        => 'CDATA Content Test',
            'post_content'      => '<p>Payload: ]]> injected</p>',
            'post_published_at' => now()->subDay(),
        ]);

        $feed = $this->feedService->generateRss();

        expect($feed)->not->toContain('<![CDATA[<p>Payload: ]]> injected</p>]]>');
        expect($feed)->toContain('<p>Payload: ]]]]><![CDATA[> injected</p>');
    });

    it('escapes CDATA-breaking sequences in Atom full content', function () {
        Post::factory()->create([
            'post_title'        => 'CDATA Content Test',
            'post_content'      => '<p>Payload: ]]> injected</p>',
            'post_published_at' => now()->subDay(),
        ]);

        $feed = $this->feedService->generateAtom();

        expect($feed)->not->toContain('<![CDATA[<p>Payload: ]]> injected</p>]]>');
        expect($feed)->toContain('<p>Payload: ]]]]><![CDATA[> injected</p>');
    });

    it('passes through content without CDATA-breaking sequences unchanged', function () {
        Post::factory()->create([
            'post_title'        => 'Normal Post',
            'post_teaser'       => 'Just a normal teaser with no special sequences',
            'post_published_at' => now()->subDay(),
        ]);

        $feed = $this->feedService->generateRss();

        expect($feed)->toContain('<![CDATA[Just a normal teaser with no special sequences]]>');
    });
});
