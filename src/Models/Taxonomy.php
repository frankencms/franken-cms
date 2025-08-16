<?php

namespace FrankenCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'hierarchical'];

    public function terms()
    {
        return $this->hasMany(Term::class);
    }
}
