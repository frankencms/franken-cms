<?php

namespace FrankenCms\Models\Scopes;

use FrankenCms\Enums\PostType;
use FrankenCms\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PostScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($model::class !== Post::class) {
            return;
        }

        $builder->where('post_type', PostType::POST->value);
    }
}
