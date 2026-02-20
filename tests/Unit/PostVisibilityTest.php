<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;

it('scopes visibleOnFrontend to only published posts with past publish date', function () {
    Post::withoutGlobalScopes()->insert([
        ['post_title' => 'Published', 'post_slug' => 'published', 'post_status' => 'published', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Draft', 'post_slug' => 'draft', 'post_status' => 'draft', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Scheduled', 'post_slug' => 'scheduled', 'post_status' => 'published', 'post_published_at' => now()->addWeek(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Private', 'post_slug' => 'private', 'post_status' => 'private', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['post_title' => 'Pending', 'post_slug' => 'pending', 'post_status' => 'pending', 'post_published_at' => now()->subDay(), 'post_type' => 'post', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $visible = Post::query()->visibleOnFrontend()->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->post_slug)->toBe('published');
});

it('isPublished returns true only for published posts with past date', function () {
    $published = Post::factory()->make([
        'post_status'       => PostStatus::PUBLISH,
        'post_published_at' => now()->subDay(),
    ]);

    $draft = Post::factory()->make([
        'post_status'       => PostStatus::DRAFT,
        'post_published_at' => now()->subDay(),
    ]);

    $future = Post::factory()->make([
        'post_status'       => PostStatus::PUBLISH,
        'post_published_at' => now()->addWeek(),
    ]);

    $nullDate = Post::factory()->make([
        'post_status'       => PostStatus::PUBLISH,
        'post_published_at' => null,
    ]);

    expect($published->isPublished())->toBeTrue()
        ->and($draft->isPublished())->toBeFalse()
        ->and($future->isPublished())->toBeFalse()
        ->and($nullDate->isPublished())->toBeFalse();
});
