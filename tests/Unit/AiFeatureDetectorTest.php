<?php

use FrankenCms\Services\AiFeatureDetector;
use FrankenCms\Settings\AiSettings;

describe('isInstalled', function () {
    test('returns true when laravel/ai is installed', function () {
        // laravel/ai is a dev dependency, so it is present in the test env
        expect(AiFeatureDetector::isInstalled())->toBeTrue();
    });
});

describe('configuredProviders', function () {
    test('returns empty array when no provider has credentials', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => null],
            'anthropic' => ['driver' => 'anthropic', 'key' => ''],
        ]);

        expect(AiFeatureDetector::configuredProviders())->toBe([]);
    });

    test('returns providers that have a non-empty key', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => 'sk-test'],
            'anthropic' => ['driver' => 'anthropic', 'key' => null],
        ]);

        expect(AiFeatureDetector::configuredProviders())->toBe(['openai' => 'Openai']);
    });

    test('includes ollama only when explicitly enabled in franken-cms config', function () {
        config()->set('ai.providers', [
            'ollama' => ['driver' => 'ollama', 'base_url' => 'http://localhost:11434'],
        ]);

        config()->set('franken-cms.ai.enable_ollama', false);
        expect(AiFeatureDetector::configuredProviders())->toBe([]);

        config()->set('franken-cms.ai.enable_ollama', true);
        expect(AiFeatureDetector::configuredProviders())->toBe(['ollama' => 'Ollama']);
    });
});

describe('isAvailable', function () {
    test('returns false when no provider is configured', function () {
        config()->set('ai.providers', []);

        expect(AiFeatureDetector::isAvailable())->toBeFalse();
    });

    test('returns false when settings are disabled even with a configured provider', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = false;
        $settings->save();

        expect(AiFeatureDetector::isAvailable())->toBeFalse();
    });

    test('returns true when installed, configured, and enabled', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isAvailable())->toBeTrue();
    });
});

describe('imageCapableProviders', function () {
    test('returns only configured providers that support image generation', function () {
        config()->set('ai.providers', [
            'openai'    => ['driver' => 'openai', 'key' => 'sk-test'],
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
            'gemini'    => ['driver' => 'gemini', 'key' => null],
        ]);

        // anthropic is configured but not image-capable; gemini is capable but unconfigured
        expect(AiFeatureDetector::imageCapableProviders())->toBe(['openai' => 'Openai']);
    });

    test('returns empty array when nothing image-capable is configured', function () {
        config()->set('ai.providers', [
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
        ]);

        expect(AiFeatureDetector::imageCapableProviders())->toBe([]);
    });
});

describe('isImageAvailable', function () {
    test('is false when no image-capable provider is configured', function () {
        config()->set('ai.providers', ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isImageAvailable())->toBeFalse();
    });

    test('is true when AI is enabled and an image-capable provider is configured', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        expect(AiFeatureDetector::isImageAvailable())->toBeTrue();
    });

    test('is false when the featured image feature is disabled in settings', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->featured_image_enabled = false;
        $settings->save();

        expect(AiFeatureDetector::isImageAvailable())->toBeFalse();
    });
});
