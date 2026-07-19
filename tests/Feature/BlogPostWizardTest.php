<?php

use FrankenCms\Ai\CmsAgent;
use FrankenCms\Livewire\BlogPostWizard;
use FrankenCms\Settings\AiSettings;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->text_provider = 'openai';
    $settings->text_model = 'gpt-4o';
    $settings->save();
});

test('generates content via streaming and advances to the review step', function () {
    CmsAgent::fake(['A full generated blog post body about coffee brewing.']);

    Livewire::test(BlogPostWizard::class)
        ->set('currentTitle', 'Coffee Brewing')
        ->set('focus', 'french press technique')
        ->set('audience', 'home baristas')
        ->set('notes', 'mention water temperature')
        ->call('generateBlogPost')
        ->assertSet('generating', false)
        ->assertSet('error', null)
        ->assertSet('step', 4)
        ->assertSet('generatedContent', 'A full generated blog post body about coffee brewing.');
});

test('surfaces generation failures and returns to the first step', function () {
    config()->set('ai.providers', []); // AI unavailable → generate() throws

    Livewire::test(BlogPostWizard::class)
        ->set('currentTitle', 'Coffee Brewing')
        ->call('generateBlogPost')
        ->assertSet('generating', false)
        ->assertSet('step', 1)
        ->assertSet('error', fn (?string $error) => filled($error));
});

test('an empty streamed response is surfaced as an error, not silent success', function () {
    CmsAgent::fake(['']);

    Livewire::test(BlogPostWizard::class)
        ->set('currentTitle', 'Coffee Brewing')
        ->call('generateBlogPost')
        ->assertSet('step', 1)
        ->assertSet('error', fn (?string $error) => str_contains((string) $error, 'no content'));
});
