<?php

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usermeta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value',
    ];

    /**
     * Get the user that owns this meta entry.
     */
    public function user()
    {
        return $this->belongsTo(config('franken-cms.models.user'), 'user_id');
    }

    protected function casts(): array
    {
        return [
            'meta_value' => 'json',
        ];
    }
}
