<?php

namespace FrankenCms\Database\Factories;

use FrankenCms\Models\Taxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxonomyFactory extends Factory
{
    protected $model = Taxonomy::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->unique()->word(),
            'hierarchical' => false,
        ];
    }

    /**
     * Indicate that the taxonomy is hierarchical.
     */
    public function hierarchical(): static
    {
        return $this->state(fn (array $attributes) => [
            'hierarchical' => true,
        ]);
    }

    /**
     * Create a category taxonomy.
     */
    public function category(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'         => 'category',
            'hierarchical' => true,
        ]);
    }

    /**
     * Create a tag taxonomy.
     */
    public function tag(): static
    {
        return $this->state(fn (array $attributes) => [
            'name'         => 'tag',
            'hierarchical' => false,
        ]);
    }
}
