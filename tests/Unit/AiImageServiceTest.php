<?php

use FrankenCms\Services\AiImageService;
use FrankenCms\Settings\AiSettings;
use FrankenCms\Settings\MediaSettings;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt;
use Laravel\Ai\Providers\OpenAiProvider;

beforeEach(function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->featured_image_enabled = true;
    $settings->save();

    $this->service = new AiImageService;
});

describe('generate', function () {
    test('throws when image generation is not available', function () {
        config()->set('ai.providers', []);

        $this->service->generate('a mountain');
    })->throws(Exception::class, 'not available');

    test('generates with the mapped aspect ratio and configured quality', function () {
        Image::fake();

        $media = app(MediaSettings::class);
        $media->featured_aspect_ratio = '4:3';
        $media->save();

        $settings = app(AiSettings::class);
        $settings->image_quality = 'high';
        $settings->save();

        $this->service->generate('a mountain');

        Image::assertGenerated(fn (ImagePrompt $prompt) => str_contains($prompt->prompt, 'a mountain')
            && $prompt->size === '4:3'
            && $prompt->quality === 'high');
    });

    test('throws when the settings-selected image provider is no longer configured', function () {
        $settings = app(AiSettings::class);
        $settings->image_provider = 'gemini'; // capable but unconfigured
        $settings->save();

        $this->service->generate('a mountain');
    })->throws(Exception::class, 'not configured');
});

describe('provider fallback', function () {
    test('routes to a configured image-capable provider when the SDK image default is unconfigured', function () {
        // The SDK ships default_for_images = gemini; only openai has a key here.
        config()->set('ai.default_for_images', 'gemini');
        Image::fake();

        $this->service->generate('a mountain');

        Image::assertGenerated(fn ($prompt) => $prompt->provider instanceof OpenAiProvider);
    });

    test('fallbackProvider defers to the SDK default when it is configured', function () {
        config()->set('ai.default_for_images', 'openai');

        $method = new ReflectionMethod($this->service, 'fallbackProvider');

        expect($method->invoke($this->service))->toBeNull();
    });

    test('fallbackProvider returns the first configured image-capable provider otherwise', function () {
        config()->set('ai.default_for_images', 'gemini');

        $method = new ReflectionMethod($this->service, 'fallbackProvider');

        expect($method->invoke($this->service))->toBe('openai');
    });
});

describe('aspectSize', function () {
    test('passes through supported ratios and maps the rest to 16:9', function () {
        $media = app(MediaSettings::class);

        foreach (['16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1', '3:2' => '3:2', '21:9' => '16:9', 'custom' => '16:9'] as $configured => $expected) {
            $media->featured_aspect_ratio = $configured;
            $media->save();

            expect($this->service->aspectSize())->toBe($expected);
        }
    });
});

describe('verifyImageModel', function () {
    test('throws when the selected provider is not configured', function () {
        $this->service->verifyImageModel('gemini', null);
    })->throws(Exception::class, 'not configured');

    test('throws when no image-capable provider exists and none is selected', function () {
        config()->set('ai.providers', ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]);

        $this->service->verifyImageModel(null, null);
    })->throws(Exception::class, 'No image-capable provider');

    test('probes with a minimal low-quality square image', function () {
        Image::fake();

        $this->service->verifyImageModel('openai', 'gpt-image-1');

        Image::assertGenerated(fn ($prompt) => $prompt->size === '1:1' && $prompt->quality === 'low');
    });

    test('throws when the model returns no image data', function () {
        Image::fake(['']);

        $this->service->verifyImageModel('openai', null);
    })->throws(Exception::class, 'no image data');
});
