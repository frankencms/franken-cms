<?php

namespace FrankenCms\Models;

use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use FrankenCms\Casts\PostContentCast;
use FrankenCms\Database\Factories\PostFactory;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Filament\Plugins\RichEditor\EnhancedImagePlugin;
use FrankenCms\Models\Scopes\PostScope;
use FrankenCms\Services\FieldRenderers\RichEditorFieldRenderer;
use FrankenCms\Settings\GeneralSettings;
use FrankenCms\Settings\MediaSettings;
use FrankenCms\Settings\SeoSettings;
use FrankenCms\Traits\HasMeta;
use FrankenCms\Traits\HasPermalinkUrl;
use FrankenCms\Traits\HasTerms;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property ?int $post_author_id
 * @property ?string $template
 */
#[ScopedBy([PostScope::class])]
class Post extends Model implements HasMedia, HasRichContent
{
    use HasFactory;
    use HasMeta;
    use HasPermalinkUrl;
    use HasTerms;
    use InteractsWithMedia;
    use InteractsWithRichContent;

    /**
     * The model to use for meta data.
     */
    protected string $metaModel = Postmeta::class;

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
        'custom_fields',
        'post_status',
        'post_published_at',
        'post_author_id',
        'post_type',
        'post_parent',
        'parent_id',
        'route_name',
    ];

    /**
     * Meta fields that should be stored in the postmeta table
     */
    protected array $metaFillable = [
        'template',
        'read_time',
        'post_teaser',
        // SEO Meta Fields
        'seo_title',
        'seo_description',
        'seo_canonical_url',
        'seo_robots_index',
        'seo_robots_follow',
        'seo_og_title',
        'seo_og_description',
        'seo_use_twitter_summary',
    ];

    /**
     * Default values for meta fields
     */
    protected array $metaDefaults = [
        'template' => 'post',
    ];

    protected $with = ['meta'];
    protected $appends = ['template', 'custom_fields'];

    /**
     * Boot the model and register model events
     */
    protected static function booted(): void
    {
        // Before saving, merge custom_fields into post_content
        static::saving(function ($post) {
            if ($post->isDirty('custom_fields')) {
                $customFields = $post->getAttributes()['custom_fields'] ?? null;

                if ($customFields !== null) {
                    // Get existing post_content or initialize as empty array
                    $content = $post->post_content ?? [];

                    // Ensure post_content is an array
                    if (! is_array($content)) {
                        $content = [];
                    }

                    // Merge custom_fields into post_content
                    $content['custom_fields'] = $customFields;

                    // Set the updated post_content
                    $post->post_content = $content;

                    // Remove custom_fields from attributes since it's now in post_content
                    unset($post->attributes['custom_fields']);
                }
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PostFactory::new();
    }

    public function template(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getMeta('template', $this->metaDefaults['template'] ?? 'post')
        );
    }

    /**
     * Accessor for custom_fields
     * Retrieves custom fields from within post_content
     */
    public function customFields(): Attribute
    {
        return Attribute::make(
            get: function () {
                $content = $this->post_content;

                // If post_content is already an array with custom_fields, return it
                if (is_array($content) && isset($content['custom_fields'])) {
                    return $content['custom_fields'];
                }

                return [];
            }
        );
    }

    public function isPublished(): bool
    {
        return $this->post_status === PostStatus::PUBLISH && $this->post_published_at !== null && $this->post_published_at->lte(now());
    }

    public function scopeVisibleOnFrontend(Builder $query): Builder
    {
        return $query
            ->where('post_status', PostStatus::PUBLISH)
            ->where('post_published_at', '<=', now());
    }

    public function getPublishedDateAttribute(): string
    {
        return $this->post_published_at->format(app(GeneralSettings::class)->date_format);
    }

    public function author()
    {
        return $this->belongsTo(config('franken-cms.models.user'), 'post_author_id');
    }

    /**
     * Get only the categories for this post
     */
    public function categories()
    {
        return $this->terms()->whereHas('taxonomy', function ($query) {
            $query->where('name', 'category');
        });
    }

    /**
     * Get only the tags for this post
     */
    public function tags()
    {
        return $this->terms()->whereHas('taxonomy', function ($query) {
            $query->where('name', 'tag');
        });
    }

    /**
     * Get the parent page (for hierarchical pages)
     */
    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id')->withoutGlobalScopes();
    }

    /**
     * Get all child pages (for hierarchical pages)
     */
    public function children()
    {
        return $this->hasMany(Post::class, 'parent_id')->withoutGlobalScopes();
    }

    /**
     * Get all ancestors (parents, grandparents, etc.) in hierarchical order
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get ancestors optimized for breadcrumbs using recursive CTE
     * Loads only required columns in a single query
     *
     * @return Collection Collection of objects with id, post_slug, post_title, parent_id
     */
    public function getBreadcrumbAncestors(): Collection
    {
        if (! $this->parent_id) {
            return collect();
        }

        $query = <<<'SQL'
            WITH RECURSIVE ancestors AS (
                SELECT id, post_slug, post_title, parent_id, 1 as level
                FROM posts
                WHERE id = ?

                UNION ALL

                SELECT p.id, p.post_slug, p.post_title, p.parent_id, a.level + 1
                FROM posts p
                INNER JOIN ancestors a ON p.id = a.parent_id
            )
            SELECT id, post_slug, post_title, parent_id
            FROM ancestors
            WHERE level > 0
            ORDER BY level DESC
        SQL;

        return collect(DB::select($query, [$this->parent_id]));
    }

    /**
     * Get the full hierarchical path (e.g., /about/team/leadership)
     */
    public function getHierarchicalPath(): string
    {
        $segments = $this->ancestors()->pluck('post_slug')->toArray();
        $segments[] = $this->post_slug;

        return '/' . implode('/', $segments);
    }

    public function setUpRichContent(): void
    {
        $this->registerRichContent('post_content')
            ->fileAttachmentProvider(
                SpatieMediaLibraryFileAttachmentProvider::make()
                    ->preserveFilenames()
                    ->mediaName(fn (TemporaryUploadedFile $file): string => Str::random() . '_' . $file->getClientOriginalName())
            )
            ->plugins([
                EnhancedImagePlugin::make(),
            ]);
    }

    /**
     * Get the media model class name
     */
    public function getMediaModel(): string
    {
        return config('media-library.media_model', Media::class);
    }

    /**
     * Register media collections for SEO images
     */
    public function registerMediaCollections(): void
    {
        // Featured image - single file only
        $this->addMediaCollection('featured')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));

        // SEO OpenGraph image - single file only
        $this->addMediaCollection('seo-og')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));

        // SEO Twitter image - single file only
        $this->addMediaCollection('seo-twitter')
            ->singleFile()
            ->useDisk(config('franken-cms.media_disk_name'));
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $mediaSettings = app(MediaSettings::class);
        $seoSettings = app(SeoSettings::class);

        // Get focal point from media custom properties (if available)
        $focalPoint = $media?->getCustomProperty('focal_point', ['x' => 50, 'y' => 50]) ?? ['x' => 50, 'y' => 50];
        $focalX = (int) ($focalPoint['x'] ?? 50);
        $focalY = (int) ($focalPoint['y'] ?? 50);

        // Thumbnail for admin table view (fixed 80x80)
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 80, 80)
//            ->focalCrop(80, 80, $focalX, $focalY)
            ->format('jpg')
            ->quality(80)
            ->performOnCollections('featured');

        // Featured image conversion (single post view)
        $featuredWidth = $mediaSettings->featured_aspect_ratio === 'custom'
            ? $mediaSettings->featured_custom_width
            : $mediaSettings->featured_width;
        $featuredHeight = $mediaSettings->getFeaturedHeight();

        if ($mediaSettings->featured_crop) {
            $conversion = $this->addMediaConversion('featured')
                ->focalCrop($featuredWidth, $featuredHeight, $focalX, $focalY)
                ->format('jpg')
                ->quality(85);

            if ($mediaSettings->enable_responsive_images) {
                $conversion->withResponsiveImages();
            }

            $conversion->performOnCollections('featured');
        } else {
            $conversion = $this->addMediaConversion('featured')
                ->fit(Fit::Contain, $featuredWidth, $featuredHeight)
                ->format('jpg')
                ->quality(85);

            if ($mediaSettings->enable_responsive_images) {
                $conversion->withResponsiveImages();
            }

            $conversion->performOnCollections('featured');
        }

        // Listing image conversion (blog index/archive pages)
        $listingWidth = $mediaSettings->listing_aspect_ratio === 'custom'
            ? $mediaSettings->listing_custom_width
            : $mediaSettings->listing_width;
        $listingHeight = $mediaSettings->getListingHeight();

        if ($mediaSettings->listing_crop) {
            $conversion = $this->addMediaConversion('listing')
                ->focalCrop($listingWidth, $listingHeight, $focalX, $focalY)
                ->format('jpg')
                ->quality(85);

            if ($mediaSettings->enable_responsive_images) {
                $conversion->withResponsiveImages();
            }

            $conversion->performOnCollections('featured');
        } else {
            $conversion = $this->addMediaConversion('listing')
                ->fit(Fit::Contain, $listingWidth, $listingHeight)
                ->format('jpg')
                ->quality(85);

            if ($mediaSettings->enable_responsive_images) {
                $conversion->withResponsiveImages();
            }

            $conversion->performOnCollections('featured');
        }

        // SEO OpenGraph image - exact 1200x630 dimensions
        $this->addMediaConversion('og')
            ->fit(Fit::Crop, 1200, 630)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('seo-og');

        // Twitter conversion for OG images - always 1200x675 for large image cards
        $this->addMediaConversion('twitter')
            ->fit(Fit::Crop, 1200, 675)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('seo-og');

        // Twitter summary card conversion - 600x600 square for dedicated summary images
        $this->addMediaConversion('twitter-summary')
            ->fit(Fit::Crop, 600, 600)
            ->format('jpg')
            ->quality(85)
            ->performOnCollections('seo-twitter');
    }

    /**
     * Override renderRichContent to add enhanced image attributes
     *
     * Uses Filament's built-in HTML sanitizer (Symfony HtmlSanitizer)
     * to prevent stored XSS from rich editor content.
     */
    public function renderRichContent(string $attribute): string
    {
        $content = $this->getAttribute($attribute);

        if (blank($content)) {
            return '';
        }

        // Get the registered rich content attribute for plugins/config
        $richContentAttribute = $this->getRichContentAttribute($attribute);

        // Build renderer with registered configuration
        $renderer = RichContentRenderer::make($content);

        if ($richContentAttribute) {
            $renderer
                ->plugins($richContentAttribute->getPlugins())
                ->customBlocks($richContentAttribute->getCustomBlocks())
                ->fileAttachmentsDisk($richContentAttribute->getFileAttachmentsDiskName())
                ->fileAttachmentsVisibility($richContentAttribute->getFileAttachmentsVisibility())
                ->fileAttachmentProvider($richContentAttribute->getFileAttachmentProvider())
                ->textColors($richContentAttribute->getTextColors());
        }

        $html = $renderer->toHtml();

        // Post-process to add enhanced image attributes from JSON
        if ($attribute === 'post_content' && ! empty($content)) {
            $fieldRenderer = app(RichEditorFieldRenderer::class);
            $html = $fieldRenderer->processEnhancedImages($html, $content);
        }

        return $html;
    }

    /**
     * Get the SEO OpenGraph image for this post
     * Checks post-specific image first, then featured image, then site default
     */
    public function seoOgImage(): ?Media
    {
        // Check for post-specific OG image
        if ($this->hasMedia('seo-og')) {
            return $this->getFirstMedia('seo-og');
        }

        // Fallback to featured image
        if ($this->hasMedia('featured')) {
            return $this->getFirstMedia('featured');
        }

        // Fallback to site default OG image
        $seoMedia = SiteSettingsMedia::getInstance();
        if ($seoMedia->hasMedia('og-default')) {
            return $seoMedia->getFirstMedia('og-default');
        }

        return null;
    }

    /**
     * Get the SEO Twitter image for this post
     * Checks post-specific image first, then falls back to featured image, then site default
     * Respects both per-post and global use_twitter_summary_card settings
     */
    public function seoTwitterImage(): ?Media
    {
        $seoSettings = app(SeoSettings::class);

        // Check per-post setting first, then fall back to global setting
        $useTwitterSummary = $this->getMeta('seo_use_twitter_summary', $seoSettings->use_twitter_summary_card);

        // If using summary cards, check for dedicated Twitter summary image
        if ($useTwitterSummary) {
            // Check for post-specific Twitter summary image
            if ($this->hasMedia('seo-twitter')) {
                return $this->getFirstMedia('seo-twitter');
            }

            // Fallback to default Twitter summary image
            $seoMedia = SiteSettingsMedia::getInstance();
            if ($seoMedia->hasMedia('twitter-default')) {
                return $seoMedia->getFirstMedia('twitter-default');
            }
        }

        // Default: Use OG image for Twitter large image cards
        // This will fall back through the same logic as seoOgImage()
        return $this->seoOgImage();
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
