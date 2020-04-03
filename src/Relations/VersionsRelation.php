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

    /**
     * @var bool
     */
    protected $withoutSelf = false;

    /**
     * @return void
     */
    public function addConstraints()
    {
        $this->withoutDefaultLocaleScope()->traitAddConstraints(function ($query) {
            if ($this->withoutSelf) {
                $query->where($this->localKey, '<>', $this->parent->getKey());
            }
        });
    }

    /**
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->withoutDefaultLocaleScope()->traitAddEagerConstraints($models, function ($query) use ($models) {
            if ($this->withoutSelf) {
                $query->whereNotIn($this->getLocalKeyName(), $this->getKeys($models, $this->getLocalKeyName()));
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return static
     */
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

    /**
     * @return $this
     */
    public function withoutSelf()
    {
        $this->withoutSelf = true;

        return $this;
    }
}
