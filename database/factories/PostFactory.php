<?php

namespace FrankenCms\Database\Factories;

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'post_title'   => $this->faker->sentence(),
            'post_slug'    => $this->faker->unique()->slug(),
            'post_content' => [
                'type'    => 'doc',
                'content' => [
                    [
                        'type'    => 'paragraph',
                        'attrs'   => ['textAlign' => 'start'],
                        'content' => [
                            ['type' => 'text', 'text' => $this->faker->paragraph()],
                        ],
                    ],
                ],
            ],
            'post_status'       => PostStatus::PUBLISH,
            'post_published_at' => now(),
            'post_author_id'    => 1, // Assuming user ID 1 exists
            'post_type'         => 'post',
            'parent_id'         => null,
            'route_name'        => null,
        ];
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status' => PostStatus::DRAFT,
        ]);
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status' => PostStatus::PUBLISH,
        ]);
    }

    /**
     * Indicate that the post is scheduled for future publication.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status'       => PostStatus::PUBLISH,
            'post_published_at' => now()->addWeek(),
        ]);
    }

    /**
     * Indicate that the post is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status' => PostStatus::PRIVATE,
        ]);
    }

    /**
     * Indicate that the post has a specific post type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => $type,
        ]);
    }

    /**
     * Indicate that the post has a parent.
     */
    public function withParent(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Indicate that the post has a specific slug.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'post_slug' => $slug,
        ]);
    }
}
