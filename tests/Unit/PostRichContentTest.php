<?php

use FrankenCms\Models\Post;

it('renderRichContent sanitizes script tags from output', function () {
    $post = Post::factory()->create([
        'post_content' => [
            'type'    => 'doc',
            'content' => [
                [
                    'type'    => 'paragraph',
                    'attrs'   => ['textAlign' => 'start'],
                    'content' => [
                        ['type' => 'text', 'text' => 'Hello world'],
                    ],
                ],
            ],
        ],
    ]);

    $html = $post->renderRichContent('post_content');

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('Hello world');
});

it('renderRichContent strips event handler attributes', function () {
    $post = Post::factory()->create([
        'post_content' => [
            'type'    => 'doc',
            'content' => [
                [
                    'type'    => 'paragraph',
                    'attrs'   => ['textAlign' => 'start'],
                    'content' => [
                        ['type' => 'text', 'text' => 'Safe content'],
                    ],
                ],
            ],
        ],
    ]);

    $html = $post->renderRichContent('post_content');

    expect($html)->not->toContain('onerror')
        ->and($html)->not->toContain('onclick');
});
