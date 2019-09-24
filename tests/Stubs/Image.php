<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Translatable;
use Makeable\LaravelTranslatable\TranslatableRelationships;

class Image extends Model
{
    use TranslatableRelationships;

    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}