<?php

namespace FrankenCms\Traits;

use FrankenCms\Models\Term;

trait HasTerms
{
    public function terms()
    {
        return $this->morphToMany(Term::class, 'termable');
    }
}
