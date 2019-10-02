<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * This is a special HasMany relation that allows us to
 * query both master and translations in a single query.
 */
class VersionsRelation extends TranslatedHasMany
{
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

        return new static($related->newQuery(), $model);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?: $parent->qualifyColumn($parent->getKeyName());
        $foreignKey = $foreignKey ?: 'master_key'; // Don't qualify master_key as is not an actual table column

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * We'll use having because master_key is a virtual field
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->withMasterKey();

            $this->query->having($this->foreignKey, '=', $this->getParentKey());

            $this->query->havingRaw("{$this->foreignKey} IS NOT NULL");
        }
    }

    /**
     * @return $this
     */
    public function withoutSelf()
    {
        $this->query->where($this->localKey, '<>', $this->parent->getKey());

        return $this;
    }
}
