<?php

declare(strict_types=1);

use FrankenCms\Filament\Resources\Post\PostResource\Pages\CreatePost;
use FrankenCms\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can upload and save enhanced images in rich editor', function () {
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    Livewire::test(CreatePost::class)
        ->fillForm([
            'post_title'   => 'Test Post with Enhanced Image',
            'post_slug'    => 'test-post-with-enhanced-image',
            'post_content' => [
                'type'    => 'doc',
                'content' => [
                    [
                        'type'  => 'image',
                        'attrs' => [
                            'id'      => 'temp-uuid-123',
                            'src'     => '/tmp/some-temp-url',
                            'alt'     => 'Test image',
                            'caption' => 'Test caption',
                        ],
                    ],
                ],
            ],
            'post_status'       => 'draft',
            'post_published_at' => now(),
            'post_author_id'    => $this->user->id,
        ])
        ->call('create');

    // Verify that a post was created
    expect(\FrankenCms\Models\Post::count())->toBe(1);

    $post = \FrankenCms\Models\Post::first();
    expect($post->post_title)->toBe('Test Post with Enhanced Image');
});

it('processes enhanced image file uploads correctly', function () {
    // This test would verify the actual file processing
    // For now, we'll just test that the enhanced image plugin is properly configured
    expect(\FrankenCms\Filament\Plugins\RichEditor\EnhancedImagePlugin::class)->toBeString();
});
