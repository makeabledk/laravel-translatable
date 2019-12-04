<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\TranslatableRelationships;

class Team extends Model
{
    use TranslatableRelationships;

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function servers()
    {
        return $this->belongsToMany(Server::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
