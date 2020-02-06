<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\TranslatableRelationships;

class User extends Model
{
    use TranslatableRelationships;

    protected $guarded = [];

    public function photo()
    {
        return $this->morphTo();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }
}
