<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedRelation;
use Makeable\LaravelTranslatable\Translatable;

class TranslatedBelongsTo extends BelongsTo
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

        $table = $this->related->getTable();

        // If parent is not a translatable table we can match directly on the foreign keys.
        // Ie. select * from posts WHERE posts.id = {$meta->post_id}
        $this->query->where($table.'.'.$this->ownerKey, '=', $this->child->{$this->foreignKey});

        // If parent is translatable we'll also accept that it matches on master_id in
        // case we're querying for a translation.
        // Ie. select * from posts WHERE posts.id = {$meta->post_id} or (posts.master_id = {$meta->post_id} and posts.master_id is not null)
        if (ModelChecker::checkTranslatable($this->related)) {
            $this->query->orWhere(function ($query) use ($table) {
                $query->where($table.'.'.$this->related->getMasterKeyName(), '=', $this->child->{$this->foreignKey})
                    ->whereNotNull($table.'.'.$this->related->getMasterKeyName());
            });
        }

        // Finally we wish to default to only fetch the parent best matching the
        // current language of the child, unless otherwise specified.
        $this->setDefaultLanguageFromModel($this->child);
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);

        $this->query->{$whereIn}($key, $modelKeys = $this->getEagerModelKeys($models));

        if (ModelChecker::checkTranslatable($this->related)) {
            $this->query->orWhere(function ($query) use ($modelKeys) {
                $query->whereIn($this->related->getTable().'.'.$this->related->getMasterKeyName(), $modelKeys)
                    ->whereNotNull($this->related->getTable().'.'.$this->related->getMasterKeyName());
            });
        }

        $this->setDefaultLanguageFromLatestQuery(Arr::first($models));
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
}
