<?php

use FrankenCms\Prompts\PromptManager;
use FrankenCms\Services\AiService;

beforeEach(function () {
    $this->promptManager = new PromptManager;
    $this->service = new AiService($this->promptManager);
});

describe('generate', function () {
    test('throws exception when AI features are not available', function () {
        // Prism is not installed in test environment, so isAvailable returns false
        $this->service->generate('generate_seo_title', ['title' => 'Test']);
    })->throws(Exception::class, 'AI features are not available');
});

describe('testConnection', function () {
    test('returns false when Prism is not installed', function () {
        expect($this->service->testConnection())->toBeFalse();
    });
});

describe('constructor', function () {
    test('accepts PromptManager dependency', function () {
        $service = new AiService($this->promptManager);

        expect($service)->toBeInstanceOf(AiService::class);
    });
});
