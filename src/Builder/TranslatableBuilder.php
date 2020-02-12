<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Makeable\LaravelTranslatable\Builder\Concerns\HasLanguageScope;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

class TranslatableBuilder extends Builder
{
    use HasLanguageScope;

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);

        $this->beforeGetting([$this, 'applyLanguageScope']);
    }

    public function hydrate(array $items)
    {
        $instance = $this->newModelInstance();

        $models = $instance->newCollection(array_map(function ($item) use ($instance) {
            $model = $instance->newFromBuilder($item);
            $model->requestedLanguage = $this->getQueryLanguageHistory();
        }, $items));

        $this->clearQueryLanguageHistory();

        return $models;
    }
}
