<?php

namespace FrankenCms\Models;

use FrankenCms\Database\Factories\TaxonomyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'hierarchical'];

    protected static function newFactory()
    {
        return TaxonomyFactory::new();
    }

    public function terms()
    {
        return $this->hasMany(Term::class);
    }
}
