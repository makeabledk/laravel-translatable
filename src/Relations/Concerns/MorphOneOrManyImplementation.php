<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedRelation;

trait MorphOneOrManyImplementation
{
    use HasOneOrManyImplementation {
        addConstraints as traitAddConstraints;
        addEagerConstraints as traitAddEagerConstraints;
    }

    public function addConstraints()
    {
        $this->traitAddConstraints(function ($query) {
            $query->where($this->morphType, $this->morphClass);
        });
    }

    public function addEagerConstraints(array $models)
    {
        $this->traitAddEagerConstraints($models, function ($query) {
            $query->where($this->morphType, $this->morphClass);
        });
    }
}
