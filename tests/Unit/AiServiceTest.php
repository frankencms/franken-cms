<?php

use FrankenCms\Prompts\PromptManager;
use FrankenCms\Services\AiService;

beforeEach(function () {
    $this->promptManager = new PromptManager;
    $this->service = new AiService($this->promptManager);
});

describe('generate', function () {
    test('throws exception when AI features are not available', function () {
        config()->set('ai.providers', []); // nothing configured

        $this->service->generate('generate_seo_title', ['title' => 'Test']);
    })->throws(Exception::class, 'AI features are not available');
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
