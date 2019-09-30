<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Translatable;

class PostMeta extends Model
{
    use Translatable;

    protected $guarded = [];

    protected $table = 'post_meta';

    protected $sync = ['post_id'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}