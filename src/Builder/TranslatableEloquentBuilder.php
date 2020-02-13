<?php

namespace Makeable\LaravelTranslatable\Builder;

use Makeable\LaravelTranslatable\Builder\Concerns\HasLanguageScopes;

class TranslatableEloquentBuilder extends EloquentBuilder
{
    use HasLanguageScopes;

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
