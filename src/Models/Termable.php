<?php

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Termable extends MorphPivot
{
    use HasFactory;

    protected $fillable = ['term_id', 'termable_id', 'termable_type'];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
