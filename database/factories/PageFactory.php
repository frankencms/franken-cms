<?php

namespace FrankenCms\Database\Factories;

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'post_title' => $this->faker->sentence(),
            'post_slug' => $this->faker->unique()->slug(),
            'post_content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'attrs' => ['textAlign' => 'start'],
                        'content' => [
                            ['type' => 'text', 'text' => $this->faker->paragraph()],
                        ],
                    ],
                ],
            ],
            'post_status' => PostStatus::PUBLISH,
            'post_published_at' => now(),
            'post_author_id' => 1, // Assuming user ID 1 exists
            'post_type' => 'page',
            'parent_id' => null,
            'route_name' => null,
        ];
    }

    /**
     * Indicate that the page is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status' => PostStatus::DRAFT,
        ]);
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_status' => PostStatus::PUBLISH,
        ]);
    }

    /**
     * Indicate that the page has a parent.
     */
    public function withParent(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Indicate that the page has a route name.
     */
    public function withRouteName(string $routeName): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $routeName,
        ]);
    }
}
