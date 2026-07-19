<?php

use FrankenCms\Services\AiImageService;
use FrankenCms\Settings\AiSettings;
use FrankenCms\Settings\MediaSettings;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt;

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
        $settings->featured_image_quality = 'high';
        $settings->save();

        $this->service->generate('a mountain');

        Image::assertGenerated(fn (ImagePrompt $prompt) => str_contains($prompt->prompt, 'a mountain')
            && $prompt->size === '4:3'
            && $prompt->quality === 'high');
    });

    test('throws when the settings-selected image provider is no longer configured', function () {
        $settings = app(AiSettings::class);
        $settings->featured_image_provider = 'gemini'; // capable but unconfigured
        $settings->save();

        $this->service->generate('a mountain');
    })->throws(Exception::class, 'not configured');
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
