<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\TranslatableRelationships;

class Comment extends Model
{
    use TranslatableRelationships;

    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
