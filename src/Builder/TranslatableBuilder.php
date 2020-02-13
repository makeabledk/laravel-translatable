<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Makeable\LaravelTranslatable\Builder\Concerns\HasLanguageScopes;

class TranslatableBuilder extends Builder
{
    use HasLanguageScopes;

//    /**
//     * @param  \Illuminate\Database\Query\Builder  $query
//     * @return void
//     */
//    public function __construct(QueryBuilder $query)
//    {
//        parent::__construct($query);
//
//        $this->beforeGetting([$this, 'applyLanguageScope'], 100);
//    }

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
