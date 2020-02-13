<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Builder\TranslatableEloquentBuilder;
use Makeable\LaravelTranslatable\ModelChecker;

trait HasOneOrManyImplementation
{
    use TranslatedRelation;

    /**
     * @param null $model
     * @return mixed
     */
    public function getParentKey($model = null)
    {
        return $this->getMasterKey($model ?? $this->parent, $this->localKey);
    }

    /**
     * @param  callable|null  $extraConstraint
     * @return void
     */
    public function addConstraints(callable $extraConstraint = null)
    {
        if (! static::$constraints) {
            return;
        }

        // Allow for disabling language scope before applying constraints
        $this
            ->setDefaultLanguageFromModel($this->parent)
            ->beforeGetting(function ($query) use ($extraConstraint) {
                $query->where($this->foreignKey, '=', $this->getParentKey());

                $query->whereNotNull($this->foreignKey);

                if ($extraConstraint) {
                    call_user_func($extraConstraint, $query);
                }

//                $this->setDefaultLanguageFromModelLanguage($query, $this->parent);
            });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @param  callable|null  $extraConstraint
     * @return void
     */
    public function addEagerConstraints(array $models, callable $extraConstraint = null)
    {
        $this
            ->setDefaultLanguageFromModel(Arr::first($models))
            ->beforeGetting(function ($query) use ($models, $extraConstraint) {
                $whereIn = $this->whereInMethod($this->parent, $this->localKey);

                $query->{$whereIn}($this->foreignKey, $this->getMasterKeys($models, $this->localKey));

                if ($extraConstraint) {
                    call_user_func($extraConstraint, $query);
                }

//                $this->setDefaultLanguageFromModelQuery($query, Arr::first($models));
            });
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $this->getMasterKey($model, $this->localKey)])) {
                $model->setRelation(
                    $relation,
                    $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        return $query->select($columns)->where(
            $this->constrainExistenceQuery($this->getExistenceCompareKey())
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        return $query->select($columns)->where(
            $this->constrainExistenceQuery($hash.'.'.$this->getForeignKeyName())
        );
    }

    /**
     * @param $compareKey
     * @return \Closure
     */
    protected function constrainExistenceQuery($compareKey)
    {
        return function (Builder $query) use ($compareKey) {
            $query->whereColumn($this->getQualifiedParentKeyName(), '=', $compareKey);

            if (ModelChecker::checkTranslatable($this->parent) && $this->queryLanguageScopeEnabled($query)) {
                $query->orWhere(function ($query) use ($compareKey) {
                    $query->whereNotNull($qualifiedMasterKey = $this->parent->qualifyColumn($this->parent->getMasterKeyName()))
                        ->whereColumn($qualifiedMasterKey, '=', $compareKey);
                });
            }
        };
    }
}
