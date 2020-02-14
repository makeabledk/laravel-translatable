<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

trait HydratesWithRequestedLanguage
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
                $model->requestedLanguage = $this->getQueryLanguageHistory();
            });
        }, $items));

        $this->clearQueryLanguageHistory();

        return $models;
    }
}
