<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Makeable\LaravelTranslatable\Relations\TranslatedBelongsTo;
use Makeable\LaravelTranslatable\Relations\TranslatedBelongsToMany;
use Makeable\LaravelTranslatable\Relations\TranslatedHasMany;
use Makeable\LaravelTranslatable\Relations\TranslatedMorphMany;
use Makeable\LaravelTranslatable\Relations\TranslatedMorphTo;

trait TranslatableRelationships
{
    /**
     * @var bool
     */
    protected $nextRelationWithoutTranslations = false;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Makeable\LaravelTranslatable\Builder\EloquentBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new \Makeable\LaravelTranslatable\Builder\EloquentBuilder($query);
    }

    /**
     * @return $this
     */
    public function nonTranslatable()
    {
        $this->nextRelationWithoutTranslations = true;

        return $this;
    }

    /**
     * Instantiate a new BelongsTo relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return $this->appropriateRelation(
            $query->getModel(),
            BelongsTo::class,
            TranslatedBelongsTo::class,
            func_get_args()
        );
    }

    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return TranslatedBelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
                                        $parentKey, $relatedKey, $relationName = null)
    {
        return $this->appropriateRelation(
            $query->getModel(),
            BelongsToMany::class,
            TranslatedBelongsToMany::class,
            func_get_args()
        );
    }

    /**
     * Instantiate a new HasMany relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return $this->appropriateRelation(
            $query->getModel(),
            HasMany::class,
            TranslatedHasMany::class,
            func_get_args()
        );
    }

    /**
     * Instantiate a new MorphMany relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return $this->appropriateRelation(
            $query->getModel(),
            MorphMany::class,
            TranslatedMorphMany::class,
            func_get_args()
        );

//        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    /**
     * Instantiate a new MorphTo relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        // For morph-to query-model and parent model is the same, so we don't know
        // at this point whether or not we'll be needing translatable.
        return $this->translatableRelationshipAllowed()
            ? new TranslatedMorphTo(...func_get_args())
            : new MorphTo(...func_get_args());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $relatedModel
     * @param  string  $originalRelation
     * @param  string  $translatableRelation
     * @param  array  $args
     * @return mixed
     */
    protected function appropriateRelation(Model $relatedModel, $originalRelation, $translatableRelation, array $args)
    {
        return $this->translatableRelationshipAllowed() && (
            ModelChecker::checkTranslatable($this) ||
            ModelChecker::checkTranslatable($relatedModel)
        )
            ? new $translatableRelation(...$args)
            : new $originalRelation(...$args);
    }

    protected function translatableRelationshipAllowed()
    {
        return tap(! $this->nextRelationWithoutTranslations, function () {
            $this->nextRelationWithoutTranslations = false; // Reset if was set
        });
    }
}
