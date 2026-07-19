<?php

use FrankenCms\Ai\CmsAgent;
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

describe('verifyTextModel', function () {
    test('throws when the provider is not configured', function () {
        config()->set('ai.providers', []);

        $this->service->verifyTextModel('openai', 'gpt-4o');
    })->throws(Exception::class, 'not configured');

    test('throws when the model responds with no content', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        CmsAgent::fake(['']);

        $this->service->verifyTextModel('openai', 'gpt-4o');
    })->throws(Exception::class, 'no content');

    test('passes silently when the model responds', function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        CmsAgent::fake(['OK']);

        $this->service->verifyTextModel('openai', 'gpt-4o');

        expect(true)->toBeTrue();
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

describe('generation results', function () {
    beforeEach(function () {
        config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->text_provider = 'openai';
        $settings->text_model = 'gpt-4o';
        $settings->save();
    });

    test('returns the trimmed model output', function () {
        CmsAgent::fake(['  A Great Coffee Title  ']);

        $result = $this->service->generate('blog_post_title', ['title' => '', 'content' => 'coffee']);

        expect($result)->toBe('A Great Coffee Title');
    });

    test('treats an empty model response as a failure, not a success', function () {
        CmsAgent::fake(['']);

        $this->service->generate('blog_post_title', ['title' => '', 'content' => 'coffee']);
    })->throws(Exception::class, 'returned no content');
});
