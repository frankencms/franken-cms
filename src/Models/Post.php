<?php

namespace FrankenCms\Models;

use FrankenCms\Casts\PostContentCast;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Scopes\PostScope;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Traits\HasMeta;
use FrankenCms\Traits\HasPermalinkUrl;
use FrankenCms\Traits\HasTerms;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ?int $post_author_id
 */
#[ScopedBy([PostScope::class])]
class Post extends Model
{
    use HasMeta;
    use HasPermalinkUrl;
    use HasTerms;

    /**
     * The model to use for meta data.
     */
    protected string $metaModel = PostMeta::class;

    /**
     * The table associated with the model.
     *
     * Although Laravel will infer 'posts', it's defined explicitly here.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * Adjust this list as needed.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_author_id',
        'post_title',
        'post_slug',
        'post_content',
        'post_status',
        'post_published_at',
        'post_password',
        'post_type',
    ];

    protected $with = ['meta'];

    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISH->value && $this->published_at <= now();
    }

    // TODO: Add featured image relationship
    public function featuredImage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PostMedia::class, 'featured_image_id', 'id');
    }

    public function getPublishedDateAttribute(): string
    {
        return $this->post_published_at->format(app(GeneralSettings::class)->date_format);
    }

    public function author()
    {
        return $this->belongsTo(config('franken-cms.models.user'), 'post_author_id');
    }

    protected function casts(): array
    {
        return [
            //            'post_content'      => PostContentCast::class,
            'post_content'      => 'array',
            'post_published_at' => 'datetime',
            'post_status'       => PostStatus::class,
        ];

    }
}
