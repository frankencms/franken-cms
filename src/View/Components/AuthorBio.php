<?php

declare(strict_types=1);

namespace FrankenCms\View\Components;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class AuthorBio extends Component
{
    public ?Model $user = null;

    public function __construct(
        int|Model|Authenticatable|null $user = null
    ) {
        if ($user instanceof Model || $user instanceof Authenticatable) {
            $this->user = $user;
        } elseif (is_int($user)) {
            $userModel = config('franken-cms.models.user');
            $this->user = $userModel::find($user);
        }

        // Load the bio relationship if not already loaded
        if ($this->user && ! $this->user->relationLoaded('bio')) {
            $this->user->load('bio');
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        // Don't render if user doesn't exist or doesn't have a bio
        if (! $this->user || ! $this->user->bio) {
            return '';
        }

        return view('franken-cms::components.author-bio');
    }
}
