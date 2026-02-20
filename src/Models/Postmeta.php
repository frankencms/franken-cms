<?php

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;

class Postmeta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'postmeta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    /**
     * Get the post that owns this meta.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function author()
    {
        return $this->belongsTo(config('franken-cms.models.user'), 'post_author_id');

    }

    protected function casts(): array
    {
        return [
            'meta_value' => 'json',
        ];
    }
}
