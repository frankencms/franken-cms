<?php

use FrankenCms\Filament\Resources\Post\Pages\CreatePost;
use FrankenCms\Filament\Resources\Post\Pages\EditPost;
use FrankenCms\Models\Post;
use FrankenCms\Tests\Models\User;
use Livewire\Livewire;

test('a teaser entered on the create page is saved', function () {
    $author = User::factory()->create();

    Livewire::test(CreatePost::class)
        ->fillForm([
            'post_title'     => 'Teaser Persistence Post',
            'post_author_id' => $author->id,
            'template'       => null,
            'post_teaser'    => 'A short excerpt written before the post exists.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('post_slug', 'teaser-persistence-post')->first();

    expect($post)->not->toBeNull()
        ->and($post->getMeta('post_teaser'))->toBe('A short excerpt written before the post exists.');
});

test('editing a teaser persists on save', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['post_author_id' => $author->id]);
    $post->setMeta('post_teaser', 'Original teaser');

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->fillForm(['post_teaser' => 'Updated teaser text', 'template' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh()->getMeta('post_teaser'))->toBe('Updated teaser text');
});
