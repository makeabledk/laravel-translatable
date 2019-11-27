<?php

namespace Makeable\LaravelTranslatable\Concerns;

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
        return $this->sync ?? [];
    }

    /**
     * @param $attributes
     * @return $this
     */
    public function forceFillMissing($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (Arr::get($this->attributes, $attribute) === null) {
                $this->{$attribute} = $value;
            }
        }

        return $this;
    }
}
