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

    protected $originalOwnerKey;

    /**
     * Get all of the relation results for a type.
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getResultsByType($type)
    {
        $instance = $this->createModelByType($type);

        $this->setOwnerKeyNameFor($instance);

        $query = $this->replayMacros($instance->newQuery())
            ->mergeConstraintsFrom($this->getQuery())
            ->with(array_merge(
                $this->getQuery()->getEagerLoads(),
                (array) ($this->morphableEagerLoads[get_class($instance)] ?? [])
            ));

        $whereIn = $this->whereInMethod($instance, $this->originalOwnerKey);

        // Ensure master key for translatable models
        $query = $query->{$whereIn}(
            $instance->getTable().'.'.$this->ownerKey, $this->gatherKeysByType($type, $instance->getKeyType())
        );

        // Add default locale
        if ($this->isTranslatableContext($instance)) {
            $this->setDefaultLocaleFromModel(Arr::first(Arr::first($this->dictionary[$type])));
            $this->applyRelationLocaleOnQuery($query);
        }

        $this->ensureMasterOnAmbiguousQueries($query);

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
        $this->setOwnerKeyNameFor($type);

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
        $this->setOwnerKeyNameFor($model);

        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $model->getAttribute($this->ownerKey) : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        return $this->parent->setRelation($this->getRelation(), $model);
    }

    /**
     * @param Model|string $model
     */
    protected function setOwnerKeyNameFor($model)
    {
        // Backup originally specified ownerKey so that we confidently overwrite it.
        // If null, we'll set false to indicate no key was specified.
        if ($this->originalOwnerKey === null) {
            $this->originalOwnerKey = $this->ownerKey ?? false;
        }

        $this->ownerKey = $this->getModelKeyName(
            ($model instanceof Model ? $model : $this->createModelByType($model)),
            $this->originalOwnerKey ?: null
        );
    }
}
