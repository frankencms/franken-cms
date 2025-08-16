<?php

namespace FrankenCms\Traits;

trait HasMeta
{
    /**
     * Get the meta records associated with the post.
     */
    public function meta()
    {
        return $this->hasMany($this->metaModel, 'post_id');
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
}
