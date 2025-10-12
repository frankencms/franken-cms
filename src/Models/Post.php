<?php

namespace FrankenCms\Models;

use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use FrankenCms\Casts\PostContentCast;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Scopes\PostScope;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Traits\HasMeta;
use FrankenCms\Traits\HasPermalinkUrl;
use FrankenCms\Traits\HasTerms;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property ?int $post_author_id
 * @property ?string $template
 */
#[ScopedBy([PostScope::class])]
class Post extends Model implements HasMedia, HasRichContent
{
    use HasMeta;
    use HasPermalinkUrl;
    use HasTerms;
    use InteractsWithMedia;
    use InteractsWithRichContent;

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

    protected $fillable = [
        'post_title',
        'post_slug',
        'post_content',
        'post_status',
        'post_published_at',
        'post_author_id',
        'post_type',
        'post_parent',
    ];

    /**
     * Meta fields that should be stored in the postmeta table
     */
    protected array $metaFillable = [
        'template',
        'read_time',
        'seo_title',
        'seo_description',
        'post_teaser',
    ];

    /**
     * Default values for meta fields
     */
    protected array $metaDefaults = [
        'template' => 'post',
    ];

    protected $with = ['meta'];
    protected $appends = ['template'];
    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISH->value && $this->published_at <= now();
    }

    public function getPublishedDateAttribute(): string
    {
        return $this->post_published_at->format(app(GeneralSettings::class)->date_format);
    }

    public function author()
    {
        return $this->belongsTo(config('franken-cms.models.user'), 'post_author_id');
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('post_content')
            ->fileAttachmentProvider(
                SpatieMediaLibraryFileAttachmentProvider::make()
                    ->preserveFilenames()
                    ->mediaName(fn (TemporaryUploadedFile $file): string => Str::random() . '_' . $file->getClientOriginalName())
            );
    }

    //    public function getRichContentAttribute(string $attribute): ?RichContentAttribute
    //    {
    //        // TODO: Implement getRichContentAttribute() method.
    //    }
    //
    //    public function renderRichContent(string $attribute): string
    //    {
    //        // TODO: Implement renderRichContent() method.
    //    }
    //
    //    public function hasRichContentAttribute(string $attribute): bool
    //    {
    //        // TODO: Implement hasRichContentAttribute() method.
    //    }

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
