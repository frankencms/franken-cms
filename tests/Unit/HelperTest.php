<?php

use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\ReadingSettings;
use FrankenCms\Settings\SeoSettings;
use FrankenCms\Settings\MediaSettings;
use FrankenCms\Settings\PermalinkSettings;

describe('setting() helper function', function () {

    it('can retrieve general settings', function () {
        $settings = app(GeneralSettings::class);
        $settings->title = 'Test Site';
        $settings->tagline = 'A test tagline';
        $settings->save();

        expect(setting('general.title'))->toBe('Test Site');
        expect(setting('general.tagline'))->toBe('A test tagline');
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
        $settings->thumbnail_width = 200;
        $settings->save();

        expect(setting('media.thumbnail_width'))->toBe(200);
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
        $settings->tagline = null;
        $settings->save();

        expect(setting('general.tagline', 'Default Tagline'))->toBeNull();
    });

    it('handles malformed keys gracefully', function () {
        expect(setting('noperiod'))->toBeNull();
        expect(setting('noperiod', 'default'))->toBe('default');
    });

    it('handles empty string values correctly', function () {
        $settings = app(GeneralSettings::class);
        $settings->tagline = '';
        $settings->save();

        expect(setting('general.tagline'))->toBe('');
        expect(setting('general.tagline', 'fallback'))->toBe('');
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
        expect(setting('media.thumbnail_width'))->not->toBeNull();
        expect(setting('permalink.permalink_structure'))->not->toBeNull();
    });

    it('handles enum value properties', function () {
        $settings = app(GeneralSettings::class);
        $settings->new_user_default_role = 'subscriber';
        $settings->save();

        expect(setting('general.new_user_default_role'))->toBe('subscriber');
    });

});

describe('cmsField() helper function', function () {

    it('returns null when no cmsFields are shared', function () {
        expect(cmsField('testField'))->toBeNull();
    });

    it('can retrieve a shared cmsField', function () {
        View::share('cmsFields', collect([
            'heroTitle' => 'Welcome to our site',
            'heroSubtitle' => 'We are awesome',
        ]));

        expect(cmsField('heroTitle'))->toBe('Welcome to our site');
        expect(cmsField('heroSubtitle'))->toBe('We are awesome');
    });

    it('returns null for non-existent field', function () {
        View::share('cmsFields', collect([
            'heroTitle' => 'Welcome',
        ]));

        expect(cmsField('nonExistent'))->toBeNull();
    });

});

describe('cmsFieldVariableName() helper function', function () {

    it('converts dot notation to camelCase', function () {
        expect(cmsFieldVariableName('hero.title'))->toBe('heroTitle');
        expect(cmsFieldVariableName('features.items'))->toBe('featuresItems');
    });

    it('converts snake_case to camelCase', function () {
        expect(cmsFieldVariableName('hero.cta_buttons'))->toBe('heroCtaButtons');
        expect(cmsFieldVariableName('section.background_color'))->toBe('sectionBackgroundColor');
    });

    it('converts kebab-case to camelCase', function () {
        expect(cmsFieldVariableName('hero.button-text'))->toBe('heroButtonText');
    });

    it('handles simple field names', function () {
        expect(cmsFieldVariableName('title'))->toBe('title');
        expect(cmsFieldVariableName('content'))->toBe('content');
    });

    it('handles multiple levels', function () {
        expect(cmsFieldVariableName('section.hero.main_title'))->toBe('sectionHeroMainTitle');
    });

});
