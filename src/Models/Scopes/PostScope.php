<?php

namespace FrankenCms\Models\Scopes;

use FrankenCms\Enums\PostType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PostScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('post_type', PostType::POST->value);

    }
}
