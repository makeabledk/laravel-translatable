<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Relations\Concerns\HasOneOrManyImplementation;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedRelation;

class TranslatedHasOne extends HasOne
{
    use HasOneOrManyImplementation;
}
