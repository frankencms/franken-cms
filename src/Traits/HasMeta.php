<?php

namespace FrankenCms\Traits;

trait HasMeta
{
    /**
     * Stores meta values that need to be saved after the model is created
     */
    protected array $pendingMeta = [];

    /**
     * Boot the HasMeta trait
     */
    protected static function bootHasMeta(): void
    {
        static::created(function ($model) {
            if (! empty($model->pendingMeta)) {
                foreach ($model->pendingMeta as $key => $value) {
                    $model->setMeta($key, $value);
                }
                $model->pendingMeta = [];
            }
        });
    }

    /**
     * Get the meta records associated with the post.
     */
    public function meta()
    {
        return $this->hasMany($this->metaModel, 'post_id');
    }

    /**
     * Override fill to intercept meta attributes during mass assignment
     */
    public function fill(array $attributes)
    {
        $metaAttributes = [];
        $regularAttributes = [];

        foreach ($attributes as $key => $value) {
            if ($this->isMetaAttribute($key)) {
                $metaAttributes[$key] = $value;
            } elseif ($this->isImageMetadataField($key)) {
                // Silently ignore image metadata fields - they're handled separately by ImageFieldSchema
                continue;
            } else {
                $regularAttributes[$key] = $value;
            }
        }

        // Fill regular attributes first
        parent::fill($regularAttributes);

        // Set meta attributes (these will go to pendingMeta)
        foreach ($metaAttributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Helper to fetch a meta value by key
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->meta()
            ->where('meta_key', $key)
            ->value('meta_value') ?? $default;
    }

    /**
     * Helper to set a meta value by key
     */
    public function setMeta(string $key, $value): void
    {
        if (! $this->exists) {
            $this->pendingMeta[$key] = $value;
            return;
        }

        // If value is null or empty string, delete the meta record
        if ($value === null || $value === '') {
            $this->deleteMeta($key);
            return;
        }

        $this->meta()->updateOrCreate(
            ['meta_key' => $key],
            ['meta_value' => $value]
        );
    }

    /**
     * Helper to delete a meta value by key
     */
    public function deleteMeta(string $key): void
    {
        $this->meta()->where('meta_key', $key)->delete();
    }

    /**
     * Override setAttribute to handle meta fields
     */
    public function setAttribute($key, $value)
    {
        if ($this->isMetaAttribute($key)) {
            $this->setMeta($key, $value);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Override getAttribute to handle meta fields
     */
    public function getAttribute($key)
    {
        if ($this->isMetaAttribute($key)) {
            $default = $this->metaDefaults[$key] ?? null;
            return $this->getMeta($key, $default);
        }

        return parent::getAttribute($key);
    }

    /**
     * Check if an attribute is a meta field
     */
    protected function isMetaAttribute(string $key): bool
    {
        return in_array($key, $this->metaFillable ?? []);
    }

    /**
     * Check if an attribute is an image metadata field
     * These are temporary fields used by ImageFieldSchema and should be ignored during fill
     */
    protected function isImageMetadataField(string $key): bool
    {
        // Don't filter out regular database columns
        $regularColumns = [
            'post_title', 'post_content', 'post_excerpt', 'post_status',
            'post_name', 'post_slug', 'post_type', 'post_author_id',
        ];

        if (in_array($key, $regularColumns)) {
            return false;
        }

        $imageMetadataPatterns = [
            '_alt',
            '_title',
            '_caption',
            '_attribution',
            '_css',
            '_lazy_loading',
            '_fetchpriority',
            '_width',
            '_height',
            '_focal_point',
        ];

        foreach ($imageMetadataPatterns as $pattern) {
            if (str_ends_with($key, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
