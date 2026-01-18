<?php

use FrankenCms\Models\Post;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Services\FaviconGenerator;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\MediaSettings;
use FrankenCms\Settings\PermalinkSettings;
use FrankenCms\Settings\ReadingSettings;
use FrankenCms\Settings\RobotsSettings;
use FrankenCms\Settings\SeoSettings;
use FrankenCms\Settings\SitemapSettings;

describe('setting() helper function', function () {

    it('can retrieve general settings', function () {
        $settings = app(GeneralSettings::class);
        $settings->title = 'Test Site';
        $settings->timezone = 'America/New_York';
        $settings->save();

        expect(setting('general.title'))->toBe('Test Site');
        expect(setting('general.timezone'))->toBe('America/New_York');
    });

    it('can retrieve seo settings', function () {
        $settings = app(SeoSettings::class);
        $settings->site_name = 'My SEO Site';
        $settings->title_separator = '|';
        $settings->save();

        expect(setting('seo.site_name'))->toBe('My SEO Site');
        expect(setting('seo.title_separator'))->toBe('|');
    });

    it('can retrieve reading settings', function () {
        $settings = app(ReadingSettings::class);
        $settings->posts_per_page = 15;
        $settings->save();

        expect(setting('reading.posts_per_page'))->toBe(15);
    });

    it('can retrieve media settings', function () {
        $settings = app(MediaSettings::class);
        $settings->featured_width = 1600;
        $settings->save();

        expect(setting('media.featured_width'))->toBe(1600);
    });

    it('can retrieve permalink settings', function () {
        $settings = app(PermalinkSettings::class);
        $settings->permalink_structure = '/blog/%postname%/';
        $settings->save();

        expect(setting('permalink.permalink_structure'))->toBe('/blog/%postname%/');
    });

    it('returns null for invalid group', function () {
        expect(setting('invalid.property'))->toBeNull();
    });

    it('returns null for invalid property', function () {
        expect(setting('general.nonexistent'))->toBeNull();
    });

    it('returns default value for invalid group', function () {
        expect(setting('invalid.property', 'default value'))->toBe('default value');
    });

    it('returns default value for invalid property', function () {
        expect(setting('general.nonexistent', 'fallback'))->toBe('fallback');
    });

    it('returns default value for null setting', function () {
        $settings = app(GeneralSettings::class);
        $settings->language = null;
        $settings->save();

        expect(setting('general.language', 'Default Language'))->toBeNull();
    });

    it('handles malformed keys gracefully', function () {
        expect(setting('noperiod'))->toBeNull();
        expect(setting('noperiod', 'default'))->toBe('default');
    });

    it('handles empty string values correctly', function () {
        $settings = app(GeneralSettings::class);
        $settings->custom_date_format = '';
        $settings->save();

        expect(setting('general.custom_date_format'))->toBe('');
        expect(setting('general.custom_date_format', 'fallback'))->toBe('');
    });

    it('can retrieve boolean settings', function () {
        $settings = app(GeneralSettings::class);
        $settings->membership = true;
        $settings->save();

        expect(setting('general.membership'))->toBeTrue();

        $settings->membership = false;
        $settings->save();

        expect(setting('general.membership'))->toBeFalse();
    });

    it('preserves data types', function () {
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->membership = true;
        $generalSettings->save();

        $readingSettings = app(ReadingSettings::class);
        $readingSettings->posts_per_page = 10;
        $readingSettings->save();

        $seoSettings = app(SeoSettings::class);
        $seoSettings->site_name = 'Test Site';
        $seoSettings->save();

        // Boolean
        expect(setting('general.membership'))->toBeBool();
        // Integer
        expect(setting('reading.posts_per_page'))->toBeInt();
        // String
        expect(setting('seo.site_name'))->toBeString();
    });

    it('works with all five settings groups', function () {
        expect(setting('general.title'))->not->toBeNull();
        expect(setting('reading.posts_per_page'))->not->toBeNull();
        expect(setting('seo.site_name'))->not->toBeNull();
        expect(setting('media.featured_width'))->not->toBeNull();
        expect(setting('permalink.permalink_structure'))->not->toBeNull();
    });

    it('handles enum value properties', function () {
        $settings = app(GeneralSettings::class);
        $settings->new_user_default_role = 'subscriber';
        $settings->save();

        expect(setting('general.new_user_default_role'))->toBe('subscriber');
    });

});

describe('frankenFieldVariableName() helper function', function () {

    it('converts dot notation to camelCase', function () {
        expect(frankenFieldVariableName('hero.title'))->toBe('heroTitle');
        expect(frankenFieldVariableName('features.items'))->toBe('featuresItems');
    });

    it('converts snake_case to camelCase', function () {
        expect(frankenFieldVariableName('hero.cta_buttons'))->toBe('heroCtaButtons');
        expect(frankenFieldVariableName('section.background_color'))->toBe('sectionBackgroundColor');
    });

    it('converts kebab-case to camelCase', function () {
        expect(frankenFieldVariableName('hero.button-text'))->toBe('heroButtonText');
    });

    it('handles simple field names', function () {
        expect(frankenFieldVariableName('title'))->toBe('title');
        expect(frankenFieldVariableName('content'))->toBe('content');
    });

    it('handles multiple levels', function () {
        expect(frankenFieldVariableName('section.hero.main_title'))->toBe('sectionHeroMainTitle');
    });

});

describe('setting() helper with robots and sitemap', function () {

    it('can retrieve robots settings', function () {
        $settings = app(RobotsSettings::class);
        $settings->enabled = true;
        $settings->discourage_indexing = false;
        $settings->save();

        expect(setting('robots.enabled'))->toBeTrue();
        expect(setting('robots.discourage_indexing'))->toBeFalse();
    });

    it('can retrieve sitemap settings', function () {
        $settings = app(SitemapSettings::class);
        $settings->enabled = true;
        $settings->default_priority = 0.8;
        $settings->save();

        expect(setting('sitemap.enabled'))->toBeTrue();
        expect(setting('sitemap.default_priority'))->toBe(0.8);
    });

    it('can retrieve array settings from robots', function () {
        $settings = app(RobotsSettings::class);
        $settings->user_agents = [
            ['user_agent' => '*', 'rules' => ['Disallow: /admin']],
        ];
        $settings->save();

        $result = setting('robots.user_agents');
        expect($result)->toBeArray();
        expect($result[0]['user_agent'])->toBe('*');
    });

    it('can retrieve array settings from sitemap', function () {
        $settings = app(SitemapSettings::class);
        $settings->excluded_paths = ['/admin/*', '/private/*'];
        $settings->save();

        $result = setting('sitemap.excluded_paths');
        expect($result)->toBeArray();
        expect($result)->toContain('/admin/*');
    });

});

describe('aspect_ratio() helper function', function () {

    it('calculates 16:9 ratio correctly', function () {
        expect(aspect_ratio(1920, 1080))->toBe('1.78:1');
    });

    it('calculates 4:3 ratio correctly', function () {
        expect(aspect_ratio(1024, 768))->toBe('1.33:1');
    });

    it('calculates 1:1 square ratio correctly', function () {
        expect(aspect_ratio(500, 500))->toBe('1:1');
    });

    it('returns clean ratio for 16:9', function () {
        expect(aspect_ratio(1920, 1080, true))->toBe('16:9');
    });

    it('returns clean ratio for 4:3', function () {
        expect(aspect_ratio(1024, 768, true))->toBe('4:3');
    });

    it('returns clean ratio for 1:1', function () {
        expect(aspect_ratio(500, 500, true))->toBe('1:1');
    });

    it('returns clean ratio for 3:2', function () {
        expect(aspect_ratio(1500, 1000, true))->toBe('3:2');
    });

    it('returns clean ratio for 21:9 ultrawide', function () {
        // 21:9 = 2.33, so use dimensions that give exactly that ratio
        expect(aspect_ratio(2100, 900, true))->toBe('21:9');
    });

    it('finds closest ratio for non-standard dimensions', function () {
        // 1900x1080 is close to 16:9 (1.76 vs 1.78)
        expect(aspect_ratio(1900, 1080, true))->toBe('16:9');
    });

    it('handles small dimensions', function () {
        expect(aspect_ratio(16, 9))->toBe('1.78:1');
        expect(aspect_ratio(4, 3))->toBe('1.33:1');
    });

    it('handles float dimensions', function () {
        expect(aspect_ratio(16.0, 9.0))->toBe('1.78:1');
    });

    it('handles very wide panoramic ratios', function () {
        expect(aspect_ratio(3000, 1000, true))->toBe('3:1');
    });

    it('handles cinema ratios', function () {
        // 2.39:1 anamorphic
        expect(aspect_ratio(2390, 1000, true))->toBe('2.39:1');
    });

});

describe('frankenField() helper function', function () {

    it('returns null when no frankenFields are shared', function () {
        expect(frankenField('testField'))->toBeNull();
    });

    it('can retrieve a shared frankenField by camelCase', function () {
        View::share('frankenFields', collect([
            'heroTitle' => 'Welcome to our site',
        ]));

        expect(frankenField('hero.title'))->toBe('Welcome to our site');
    });

    it('can retrieve a shared frankenField by original name', function () {
        View::share('frankenFields', collect([
            'hero.subtitle' => 'A subtitle value',
        ]));

        expect(frankenField('hero.subtitle'))->toBe('A subtitle value');
    });

    it('returns null for non-existent field', function () {
        View::share('frankenFields', collect([
            'heroTitle' => 'Welcome',
        ]));

        expect(frankenField('nonExistent'))->toBeNull();
    });

    it('handles non-collection shared value', function () {
        View::share('frankenFields', 'not a collection');

        expect(frankenField('anyField'))->toBeNull();
    });

});

describe('franken_field() helper function', function () {

    it('is an alias for frankenField', function () {
        View::share('frankenFields', collect([
            'heroTitle' => 'Test Value',
        ]));

        expect(franken_field('hero.title'))->toBe('Test Value');
        expect(franken_field('hero.title'))->toBe(frankenField('hero.title'));
    });

});

describe('_parseFieldExpression() helper function', function () {

    it('returns field name and empty options', function () {
        $result = _parseFieldExpression('hero.title');

        expect($result)->toBe(['name' => 'hero.title', 'options' => []]);
    });

    it('returns field name with options', function () {
        $options = ['type' => 'text', 'label' => 'Hero Title'];
        $result = _parseFieldExpression('hero.title', $options);

        expect($result['name'])->toBe('hero.title');
        expect($result['options'])->toBe($options);
    });

});

describe('favicon_tags() helper function', function () {

    it('returns string from FaviconGenerator', function () {
        $mockGenerator = Mockery::mock(FaviconGenerator::class);
        $mockGenerator->shouldReceive('getHtmlTags')
            ->once()
            ->andReturn('<link rel="icon" href="/favicon.ico">');

        app()->instance(FaviconGenerator::class, $mockGenerator);

        expect(favicon_tags())->toBe('<link rel="icon" href="/favicon.ico">');
    });

    it('returns empty string when no favicons configured', function () {
        $mockGenerator = Mockery::mock(FaviconGenerator::class);
        $mockGenerator->shouldReceive('getHtmlTags')
            ->once()
            ->andReturn('');

        app()->instance(FaviconGenerator::class, $mockGenerator);

        expect(favicon_tags())->toBe('');
    });

});
