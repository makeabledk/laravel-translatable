<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

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
