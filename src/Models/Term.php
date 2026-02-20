<?php

namespace FrankenCms\Models;

use FrankenCms\Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'taxonomy_id', 'parent_id', 'description'];

    protected static function newFactory()
    {
        return TermFactory::new();
    }

    protected $appends = ['url'];

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function children()
    {
        return $this->hasMany(Term::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    /**
     * Get all posts that have this term
     */
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'termable');
    }

    /**
     * Get all pages that have this term
     */
    public function pages()
    {
        return $this->morphedByMany(Page::class, 'termable');
    }

    /**
     * Get the URL for the term's archive page
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Load taxonomy if not already loaded
                if (! $this->relationLoaded('taxonomy')) {
                    $this->load('taxonomy');
                }

                $taxonomyName = $this->taxonomy->name ?? 'term';

                // Generate URL like /category/slug or /tag/slug
                return url("/{$taxonomyName}/{$this->slug}");
            }
        );
    }

    /**
     * Scope to only get terms for a specific taxonomy by name
     */
    public function scopeForTaxonomy($query, string $taxonomyName)
    {
        return $query->whereHas('taxonomy', function ($q) use ($taxonomyName) {
            $q->where('name', $taxonomyName);
        });
    }
}
