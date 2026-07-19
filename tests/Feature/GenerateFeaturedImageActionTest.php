<?php

use FrankenCms\Filament\Actions\GenerateFeaturedImageAction;
use FrankenCms\Models\Post;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

// A valid 1x1 transparent PNG, base64-encoded. Needed because the media
// library synchronously runs image conversions (queue driver is "sync" in
// tests), so the fake generated image must be real, loadable image bytes.
const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

beforeEach(function () {
    config()->set('ai.providers', ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]);
    $settings = app(AiSettings::class);
    $settings->enabled = true;
    $settings->featured_image_enabled = true;
    $settings->featured_image_prompt = 'Header about: {title}';
    $settings->save();

    Storage::fake('local');
});

test('fills the prompt template from post data', function () {
    $prompt = GenerateFeaturedImageAction::fillPromptTemplate('Header about: {title}, focus {excerpt}', [
        'title'   => 'My Post',
        'excerpt' => 'A short teaser',
    ]);

    expect($prompt)->toBe('Header about: My Post, focus A short teaser');
});

test('unknown placeholders are stripped', function () {
    $prompt = GenerateFeaturedImageAction::fillPromptTemplate('About: {title} {nonsense}', ['title' => 'X']);

    expect($prompt)->toBe('About: X');
});

test('the modal default prompt maps post_teaser into the excerpt placeholder', function () {
    $settings = app(AiSettings::class);
    $settings->featured_image_prompt = 'Header about: {title}, focus {excerpt}';
    $settings->save();

    // PostForm's live form data uses `post_teaser`, not `post_excerpt`/`excerpt` (see
    // src/Filament/Resources/Post/Schemas/PostForm.php:88) — pin that field-name mapping.
    $prompt = GenerateFeaturedImageAction::defaultPromptFor([
        'post_title'  => 'T',
        'post_teaser' => 'E',
    ]);

    expect($prompt)->toBe('Header about: T, focus E');
});

test('generates and attaches the image to the featured collection', function () {
    Image::fake([TINY_PNG_BASE64]);

    $post = Post::factory()->create();

    GenerateFeaturedImageAction::generateAndAttach($post, 'a mountain at dusk');

    expect($post->refresh()->hasMedia('featured'))->toBeTrue();
    Image::assertGenerated(fn ($prompt) => str_contains($prompt->prompt, 'a mountain at dusk'));
});

test('replaces an existing featured image', function () {
    Image::fake([TINY_PNG_BASE64, TINY_PNG_BASE64]);

    $post = Post::factory()->create();
    GenerateFeaturedImageAction::generateAndAttach($post, 'first image');
    GenerateFeaturedImageAction::generateAndAttach($post, 'second image');

    expect($post->refresh()->getMedia('featured'))->toHaveCount(1);
});
