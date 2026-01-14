<?php

namespace FrankenCms\Database\Factories;

use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TermFactory extends Factory
{
    protected $model = Term::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'taxonomy_id' => Taxonomy::factory(),
            'parent_id'   => null,
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Associate with a specific taxonomy.
     */
    public function forTaxonomy(Taxonomy $taxonomy): static
    {
        return $this->state(fn (array $attributes) => [
            'taxonomy_id' => $taxonomy->id,
        ]);
    }

    /**
     * Set a parent term.
     */
    public function withParent(Term $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id'   => $parent->id,
            'taxonomy_id' => $parent->taxonomy_id,
        ]);
    }

    /**
     * Set a specific slug.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }
}
