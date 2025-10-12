<?php

namespace FrankenCms\Traits;

trait HasMeta
{
    /**
     * Stores meta values that need to be saved after the model is created
     */
    protected array $pendingMeta = [];

    /**
     * Get the meta records associated with the post.
     */
    public function meta()
    {
        return $this->hasMany($this->metaModel, 'post_id');
    }

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
     * Override fill to intercept meta attributes during mass assignment
     */
    public function fill(array $attributes)
    {
        $metaAttributes = [];
        $regularAttributes = [];

        foreach ($attributes as $key => $value) {
            if ($this->isMetaAttribute($key)) {
                $metaAttributes[$key] = $value;
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
     * Check if an attribute is a meta field
     */
    protected function isMetaAttribute(string $key): bool
    {
        return in_array($key, $this->metaFillable ?? []);
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
}
