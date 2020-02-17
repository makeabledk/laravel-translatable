<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Relations\Concerns\BelongsToBaseImplementation;

class TranslatedMorphTo extends MorphTo
{
    use BelongsToBaseImplementation;

    /**
     * Get all of the relation results for a type.
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getResultsByType($type)
    {
        $instance = $this->createModelByType($type);

        $query = $this->replayMacros($instance->newQuery())
            ->mergeConstraintsFrom($this->getQuery())
            ->with(array_merge(
                $this->getQuery()->getEagerLoads(),
                (array) ($this->morphableEagerLoads[get_class($instance)] ?? [])
            ));

        $whereIn = $this->whereInMethod($instance, $this->ownerKey);

        // Ensure master key for translatable models
        $ownerKey = $this->getMasterKeyName($this->createModelByType($type), $this->ownerKey);

        $query = $query->{$whereIn}(
            $instance->getTable().'.'.$ownerKey, $this->gatherKeysByType($type)
        );

        // Add default language
        if ($this->isTranslatableContext($instance)) {
            $this->setDefaultLanguageFromModel(Arr::first(Arr::first($this->dictionary[$type])));
            $this->applyRelationLanguageOnQuery($query);
        }

        return $query->get();
    }

    /**
     * Match the results for a given type to their parents.
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return void
     */
    protected function matchToMorphParents($type, Collection $results)
    {
        $this->ownerKey = $this->getMasterKeyName(
            $model = $this->createModelByType($type),
            $this->ownerKey
        );

        return parent::matchToMorphParents($type, $results);
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $this->getMasterKey($model, $this->ownerKey) : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        return $this->parent->setRelation($this->getRelation(), $model);
    }

    /**
     * Polyfill for different Laravel versions.
     *
     * @return mixed|string
     */
    public function getRelation()
    {
        return property_exists($this, 'relation')
            ? $this->relation
            : $this->relationName;
    }
}
