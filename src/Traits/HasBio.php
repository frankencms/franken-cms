<?php

declare(strict_types=1);

namespace FrankenCms\Traits;

use FrankenCms\Models\UserBio;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasBio
{
    /**
     * Get the user's bio
     */
    public function bio(): HasOne
    {
        return $this->hasOne(UserBio::class);
    }

    /**
     * Check if the user has a bio
     */
    public function hasBio(): bool
    {
        return $this->bio()->exists();
    }

    /**
     * Get the user's bio or create a new one if it doesn't exist
     */
    public function getOrCreateBio(): UserBio
    {
        return $this->bio()->firstOrCreate([
            'user_id' => $this->id,
        ]);
    }
}
