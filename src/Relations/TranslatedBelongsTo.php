<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Relations\Concerns\BelongsToBaseImplementation;

class TranslatedBelongsTo extends BelongsTo
{
    use BelongsToBaseImplementation;

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this
            ->setDefaultLocaleFromModel(Arr::first($models))
            ->beforeGetting(function ($query) use ($models) {
                $query->where(function ($query) use ($models) {
                    // We'll grab the primary key name of the related models since it could be set to
                    // a non-standard name and not "id". We will then construct the constraint for
                    // our eagerly loading query so it returns the proper models from execution.
                    $ownerKey = $this->getModelKeyName($this->related, $this->ownerKey);

                    $key = $this->related->getTable().'.'.$ownerKey;

                    $whereIn = $this->whereInMethod($this->related, $ownerKey);

                    $query->{$whereIn}($key, $this->getEagerModelKeys($models));

                    $this->ensureMasterOnAmbiguousQueries($query);
                });
            });
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        $ownerKey = $model instanceof Model ? $this->getModelKey($model, $this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->getRelation(), $model);
        } elseif ($this->child->isDirty($this->foreignKey)) {
            $this->child->unsetRelation($this->getRelation());
        }

        return $this->child;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$this->getModelKey($result, $owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
    }
}
