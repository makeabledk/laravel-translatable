<?php

namespace Makeable\LaravelTranslatable\Builder;

use Makeable\LaravelTranslatable\Builder\Concerns\HasLocaleScopes;
use Makeable\LaravelTranslatable\Builder\Concerns\HydratesWithRequestedLocale;

class TranslatableEloquentBuilder extends EloquentBuilder
{
    use HasLocaleScopes,
        HydratesWithRequestedLocale;

    public function getScopes()
    {
        return $this->scopes;
    }
}
