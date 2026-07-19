<?php

use FrankenCms\Ai\CmsAgent;
use FrankenCms\Filament\Resources\Post\Pages\CreatePost;
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

test('slug derives from title on the create page', function () {
    Livewire::test(CreatePost::class)
        ->fillForm(['post_title' => 'Hello Slug World'])
        ->assertSchemaStateSet(['post_slug' => 'hello-slug-world']);
});

test('slug derives when the title comes from the AI generate action', function () {
    CmsAgent::fake(['My Generated Title']);

    Livewire::test(CreatePost::class)
        ->fillForm(['post_content' => 'Some draft content about slugs.'])
        ->callFormComponentAction('post_title', 'generate_blog_post_title')
        ->assertSchemaStateSet([
            'post_title' => 'My Generated Title',
            'post_slug'  => 'my-generated-title',
        ]);
});
