<?php

use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Models\Post;
use FrankenCms\Settings\PermalinkSettings;
use FrankenCms\Tests\Models\User;

test('resolves a url for a post whose author id has no matching user', function () {
    $settings = app(PermalinkSettings::class);
    $settings->permalink_structure = PermalinkStructure::POST_NAME->value;
    $settings->save();

    $post = Post::factory()->create(['post_author_id' => 999999]);

    expect($post->url)->toContain($post->post_slug);
});

test('falls back to guest for the author placeholder when the author is missing', function () {
    $settings = app(PermalinkSettings::class);
    $settings->permalink_structure = PermalinkStructure::CUSTOM->value;
    $settings->custom_permalink_structure = ['%author%', '%postname%'];
    $settings->save();

    $post = Post::factory()->create(['post_author_id' => 999999]);

    expect($post->url)->toContain('/guest/');
});

test('uses the author name slug when the author exists', function () {
    $settings = app(PermalinkSettings::class);
    $settings->permalink_structure = PermalinkStructure::CUSTOM->value;
    $settings->custom_permalink_structure = ['%author%', '%postname%'];
    $settings->save();

    $author = User::factory()->create(['name' => 'Mary Shelley']);
    $post = Post::factory()->create(['post_author_id' => $author->id]);

    expect($post->url)->toContain('/mary-shelley/');
});
