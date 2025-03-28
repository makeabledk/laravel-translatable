<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

trait HasOneOrManyImplementation
{
    use TranslatedRelation;

    /**
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->getModelKey($this->parent, $this->localKey);
    }

    /**
     * @return mixed
     */
    public function getParentKeyName()
    {
        return $this->getModelKeyName($this->parent, $this->localKey);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        return $this->parent->qualifyColumn($this->getParentKeyName());
    }

    /**
     * @param  callable|null  $extraConstraint
     * @return void
     */
    public function addConstraints(?callable $extraConstraint = null)
    {
        if (! static::$constraints) {
            return;
        }

        // Allow for disabling locale scope before applying constraints
        $this
            ->setDefaultLocaleFromModel($this->parent)
            ->beforeGetting(function ($query) use ($extraConstraint) {
                $query->where($this->foreignKey, '=', $this->getParentKey());

                $query->whereNotNull($this->foreignKey);

                if ($extraConstraint) {
                    call_user_func($extraConstraint, $query);
                }
            });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @param  callable|null  $extraConstraint
     * @return void
     */
    public function addEagerConstraints(array $models, ?callable $extraConstraint = null)
    {
        $this
            ->setDefaultLocaleFromModel(Arr::first($models))
            ->beforeGetting(function ($query) use ($models, $extraConstraint) {
                $whereIn = $this->whereInMethod($this->parent, $this->localKey);

                $query->{$whereIn}($this->foreignKey, $this->getModelKeys($models, $this->localKey));

                if ($extraConstraint) {
                    call_user_func($extraConstraint, $query);
                }
            });
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $this->localKey = $this->getParentKeyName();

        return parent::matchOneOrMany($models, $results, $relation, $type);
    }
}
