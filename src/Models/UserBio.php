<?php

declare(strict_types=1);

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBio extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'website',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * Get the user that owns this bio
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('franken-cms.models.user'));
    }

    /**
     * Get a specific social link by key
     */
    public function getSocialLink(string $key): ?string
    {
        return $this->social_links[$key] ?? null;
    }

    /**
     * Set a social link
     */
    public function setSocialLink(string $key, ?string $value): void
    {
        $links = $this->social_links ?? [];
        $links[$key] = $value;
        $this->social_links = $links;
    }
}
