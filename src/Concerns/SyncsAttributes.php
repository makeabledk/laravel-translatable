<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

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
    public function getChangedSyncAttributes(array $original = null)
    {
        if ($original !== null) {
            $changes = [];

            foreach ($original as $key => $value) {
                if ($this->getAttribute($key) !== $value) {
                    $changes[$key] = $this->getAttribute($key);
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
            if (Arr::has($this->attributes, $attribute) || ! method_exists($this, $attribute)) {
                $attributes[] = $attribute;
                continue;
            }

            // If a belongs-to relation was passed, we'll use the underlying foreign keys
            $relation = $this->$attribute();

            if ($relation instanceof BelongsTo) {
                $attributes[] = method_exists($relation, 'getForeignKey')
                    ? $relation->getForeignKey()
                    : $relation->getForeignKeyName();
            }

            if ($this->$attribute() instanceof MorphTo) {
                $attributes[] = $this->$attribute()->getMorphType();
            }
        }

        return $attributes;
    }

    /**
     * @param $attributes
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
}
