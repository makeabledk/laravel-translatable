<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Makeable\LaravelTranslatable\Relations\Concerns\HasOneOrManyImplementation;

class TranslatedHasOne extends HasOne
{
    use HasOneOrManyImplementation;
}
