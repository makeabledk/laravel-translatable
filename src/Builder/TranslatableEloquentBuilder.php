<?php

namespace Makeable\LaravelTranslatable\Builder;

use Makeable\LaravelTranslatable\Builder\Concerns\HasLanguageScopes;
use Makeable\LaravelTranslatable\Builder\Concerns\HydratesWithRequestedLanguage;

class TranslatableEloquentBuilder extends EloquentBuilder
{
    use HasLanguageScopes,
        HydratesWithRequestedLanguage;
}
