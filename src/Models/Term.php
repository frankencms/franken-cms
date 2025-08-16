<?php

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'taxonomy_id', 'parent_id'];

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function children()
    {
        return $this->hasMany(Term::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    public function termables()
    {
        return $this->morphToMany('App\Models\Termable', 'termable');
    }
}
