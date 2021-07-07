<?php

namespace Makeable\LaravelTranslatable\Relations;

class SiblingsRelation extends VersionsRelation
{
    public function addConstraints()
    {
        parent::addConstraints();

        if (static::$constraints) {
            $this->where($this->localKey, '<>', $this->parent->getKey());
        }
    }

    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->whereNotIn($this->getLocalKeyName(), $this->getKeys($models, $this->getLocalKeyName()));
    }
}
