<?php

namespace Makeable\LaravelTranslatable\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Translatable;

class Tag extends Model
{
    use Translatable;

    protected $guarded = [];

    public function taggable()
    {
        return $this->morphTo();
    }
}