<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait BelongsToImplementation
{
    use TranslatedRelation;

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (! static::$constraints) {
            return;
        }

        $this->beforeGetting(function ($query) {
            $query->where(function ($query) {
                // Ie. select * from posts WHERE posts.id = {$meta->post_id}
                // If parent is not a translatable table we can match directly on the foreign keys.
                $table = $this->related->getTable();

                $ownerKey = $this->getMasterKeyName($this->related, $this->ownerKey);

                $query->where($table.'.'.$ownerKey, '=', $this->child->{$this->foreignKey});

//                // If parent is translatable we'll also accept that it matches on master_id in case we're querying for a translation.
//                // Ie. select * from posts WHERE posts.id = {$meta->post_id} or (posts.master_id = {$meta->post_id} and posts.master_id is not null)
//                // If language scope has been explicitly disabled we'll avoid corrupting the query.
//                if (ModelChecker::checkTranslatable($this->related) && $this->query->languageScopeEnabled) {
//                    $query->orWhere(function ($query) use ($table) {
//                        $query->where($table.'.'.$this->related->getMasterKeyName(), '=', $this->child->{$this->foreignKey})
//                            ->whereNotNull($table.'.'.$this->related->getMasterKeyName());
//                    });
//                }
            });

            // Finally we wish to default to only fetch the parent best matching the
            // current language of the child, unless otherwise specified.
            $this->setDefaultLanguageFromModelLanguage($query, $this->child);
        });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->beforeGetting(function ($query) use ($models) {
            $query->where(function ($query) use ($models) {
                // We'll grab the primary key name of the related models since it could be set to
                // a non-standard name and not "id". We will then construct the constraint for
                // our eagerly loading query so it returns the proper models from execution.
                $ownerKey = $this->getMasterKeyName($this->related, $this->ownerKey);

                $key = $this->related->getTable().'.'.$ownerKey;

                $whereIn = $this->whereInMethod($this->related, $ownerKey);

                $query->{$whereIn}($key, $modelKeys = $this->getEagerModelKeys($models));
//
//                // We'll check if related is translatable & we should apply language scope on this query.
//                // If language scope has been explicitly disabled we'll avoid corrupting the query.
//                if (ModelChecker::checkTranslatable($this->related) && $this->query->languageScopeEnabled) {
//                    $query->orWhere(function ($query) use ($modelKeys) {
//                        $query->whereIn($this->related->getTable().'.'.$this->related->getMasterKeyName(), $modelKeys)
//                            ->whereNotNull($this->related->getTable().'.'.$this->related->getMasterKeyName());
//                    });
//                }
            });

            $this->setDefaultLanguageFromModelQuery($query, Arr::first($models));
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
        $ownerKey = $model instanceof Model ? $this->getMasterKey($model, $this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->relationName, $model);
        } elseif ($this->child->isDirty($this->foreignKey)) {
            $this->child->unsetRelation($this->relationName);
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
            $dictionary[$this->getMasterKey($result, $owner)] = $result;
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
