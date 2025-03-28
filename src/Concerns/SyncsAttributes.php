<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait SyncsAttributes
{
    /**
     * @var bool
     */
    public $syncingInProgress = false;

    /**
     * @param  array|null  $original
     * @return array
     */
    public function getChangedSyncAttributes(?array $original = null)
    {
        if ($original !== null) {
            [$changes, $syncAttributesNames] = [[], $this->getSyncAttributeNames()];

            foreach ($syncAttributesNames as $attributeName) {
                if ($this->getAttribute($attributeName) !== Arr::get($original, $attributeName)) {
                    $changes[$attributeName] = $this->getAttribute($attributeName);
                }
            }
        }

        return Arr::only($changes ?? $this->getChanges(), $this->getSyncAttributeNames());
    }

    /**
     * @return array
     */
    public function getSyncAttributes()
    {
        return $this->only($this->getSyncAttributeNames());
    }

    /**
     * @return array
     */
    public function getSyncAttributeNames()
    {
        $attributes = [];

        foreach ($this->sync ?? [] as $attribute) {
            if (Arr::has($this->attributes, $attribute) || ! method_exists($this, $relationName = Str::camel($attribute))) {
                $attributes[] = $attribute;
                continue;
            }

            // If a belongs-to relation was passed, we'll use the underlying foreign keys
            $relation = $this->$relationName();

            if ($relation instanceof BelongsTo) {
                $attributes[] = method_exists($relation, 'getForeignKey')
                    ? $relation->getForeignKey()
                    : $relation->getForeignKeyName();
            }

            if ($relation instanceof MorphTo) {
                $attributes[] = $relation->getMorphType();
            }
        }

        return $attributes;
    }

    /**
     * @param  $attributes
     * @return $this
     */
    public function forceFillMissing($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (Arr::get($this->attributes, $attribute) === null) {
                $this->setAttribute($attribute, $value);
            }
        }

        return $this;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    public function syncAttributesFromSibling(Model $model)
    {
        if (! $this->is($model)) {
            $this->syncingInProgress = true;
            $this->forceFill($model->getSyncAttributes())->save();
            $this->syncingInProgress = false;
        }

        return $this;
    }
}
