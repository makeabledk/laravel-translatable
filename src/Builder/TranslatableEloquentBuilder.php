<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Makeable\LaravelTranslatable\Builder\Concerns\HasLanguageScopes;

class TranslatableEloquentBuilder extends EloquentBuilder
{
    use HasLanguageScopes;

    public function __construct(Builder $query)
    {
        parent::__construct($query);

        $this->beforeGetting(function () {
            $this->applyCurrentLanguageWhenApplicable();
        }, 1000);
    }

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
