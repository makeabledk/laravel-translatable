<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Makeable\LaravelTranslatable\Relations\Concerns\HasOneOrManyImplementation;
use Makeable\LaravelTranslatable\TranslatableField;

/**
 * This is a special HasMany relation that allows us to
 * query both master and translations in a single query.
 */
class VersionsRelation extends HasMany
{
    use HasOneOrManyImplementation {
        addConstraints as traitAddConstraints;
        addEagerConstraints as traitAddEagerConstraints;
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $this->withoutLocaleScope()->withoutDefaultLocaleScope();

            $this->where(TranslatableField::$sibling_id, $this->parent->getSiblingKey());
        }
    }

    public function addEagerConstraints(array $models)
    {
        $this->withoutLocaleScope()->withoutDefaultLocaleScope();

        $this->whereIn(TranslatableField::$sibling_id, $this->getKeys($models, TranslatableField::$sibling_id));
    }

    public static function model(Model $model)
    {
        $class = get_class($model);

        // Reimplemented protected newRelatedInstance
        $related = tap(new $class, function ($instance) use ($model) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($model->getConnectionName());
            }
        });

        return new static($related->newQuery(), $model, TranslatableField::$sibling_id, $model->getKeyName());
    }
}
