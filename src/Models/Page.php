<?php

namespace FrankenCms\Models;

use FrankenCms\Database\Factories\PageFactory;
use FrankenCms\Models\Scopes\PageScope;
use FrankenCms\Observers\PageObserver;
use FrankenCms\Traits\HasMeta;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property ?int $post_author_id
 * @property ?string $template
 */
#[ObservedBy(PageObserver::class)]
#[ScopedBy(PageScope::class)]
class Page extends Post
{
    use HasFactory;
    use HasMeta;

    /**
     * Default values for meta fields specific to pages
     */
    protected array $metaDefaults = [
        'template' => 'page-home',
    ];

    protected $with = ['meta'];
    protected $appends = ['template', 'custom_fields'];

    public function template(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getMeta('template', $this->metaDefaults['template'] ?? 'page-home')
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
