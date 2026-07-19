<?php

use FrankenCms\Prompts\PromptManager;
use FrankenCms\Services\AiService;
use FrankenCms\Settings\AiSettings;
use Laravel\Ai\Files\LocalImage;
use Laravel\Ai\Files\RemoteImage;

beforeEach(function () {
    $this->promptManager = new PromptManager;
    $this->service = new AiService($this->promptManager);
    $this->buildImageAttachments = fn (?string $imageUrl, ?string $imagePath) => (new ReflectionMethod(AiService::class, 'buildImageAttachments'))
        ->invoke($this->service, $imageUrl, $imagePath);
});

describe('generate', function () {
    test('throws exception when AI features are not available', function () {
        config()->set('ai.providers', []); // nothing configured

        $this->service->generate('generate_seo_title', ['title' => 'Test']);
    })->throws(Exception::class, 'AI features are not available');

    test('throws exception when the stored provider is not configured', function () {
        config()->set('ai.providers', [
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-ant-test'],
        ]);

        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->text_provider = 'openai';
        $settings->save();

        $this->service->generate('generate_seo_title', ['title' => 'Test']);
    })->throws(Exception::class, 'not configured');
});

describe('testConnection', function () {
    test('returns false when no provider is configured', function () {
        config()->set('ai.providers', []);

        expect($this->service->testConnection())->toBeFalse();
    });
});

describe('constructor', function () {
    test('accepts PromptManager dependency', function () {
        expect(new AiService($this->promptManager))->toBeInstanceOf(AiService::class);
    });
});

describe('buildImageAttachments', function () {
    test('returns a RemoteImage when a URL is given outside the local environment', function () {
        $this->app['env'] = 'production';

        $attachments = ($this->buildImageAttachments)('https://example.com/photo.jpg', null);

        expect($attachments)->toHaveCount(1)
            ->and($attachments[0])->toBeInstanceOf(RemoteImage::class);
    });

    test('returns a LocalImage when an existing image path is given', function () {
        $imagePath = tempnam(sys_get_temp_dir(), 'franken-ai-test-') . '.png';
        file_put_contents($imagePath, 'fake-image-bytes');

        try {
            $attachments = ($this->buildImageAttachments)(null, $imagePath);

            expect($attachments)->toHaveCount(1)
                ->and($attachments[0])->toBeInstanceOf(LocalImage::class);
        } finally {
            unlink($imagePath);
        }
    });

    test('returns an empty array when both URL and path are null', function () {
        $attachments = ($this->buildImageAttachments)(null, null);

        expect($attachments)->toBe([]);
    });

    test('returns an empty array when the image path does not exist', function () {
        $attachments = ($this->buildImageAttachments)(null, '/nonexistent/path/to/image.png');

        expect($attachments)->toBe([]);
    });
});
