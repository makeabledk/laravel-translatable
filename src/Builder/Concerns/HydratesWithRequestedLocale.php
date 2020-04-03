<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

trait HydratesWithRequestedLocale
{
    /**
     * @param  array  $items
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydrate(array $items)
    {
        $instance = $this->newModelInstance();

        $models = $instance->newCollection(array_map(function ($item) use ($instance) {
            return tap($instance->newFromBuilder($item), function ($model) {
                $model->requestedLocale = $this->getLocaleQueryHistory();
            });
        }, $items));

        $this->clearLocaleQueryHistory();

        return $models;
    }
}
