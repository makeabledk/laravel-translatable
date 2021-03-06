<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Translatable;

class Post extends Model
{
    use Translatable;

    protected $guarded = [];

    public $sync = ['team_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function meta()
    {
        return $this->hasMany(PostMeta::class);
    }

    public function directMeta()
    {
        return $this->hasMany(PostMeta::class)->withoutLocaleScope();
    }

    public function images()
    {
        return $this->belongsToMany(Image::class);
    }

    public function tags()
    {
        return $this->morphMany(Tag::class, 'taggable');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
