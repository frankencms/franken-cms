<?php

namespace FrankenCms\Models;

use FrankenCms\Models\Scopes\PageScope;
use FrankenCms\Observers\PageObserver;
use FrankenCms\Traits\HasMeta;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property ?int $post_author_id
 * @property ?string $template
 */
#[ObservedBy(PageObserver::class)]
#[ScopedBy(PageScope::class)]
class Page extends Post
{
    use HasMeta;

    /**
     * Default values for meta fields specific to pages
     */
    protected array $metaDefaults = [
        'template' => 'page-home',
    ];

    protected $with = ['meta'];
    protected $appends = ['template'];

    public function template(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getMeta('template', $this->metaDefaults['template'] ?? 'page-home')
        );
    }
}
