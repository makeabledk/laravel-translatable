<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Makeable\LaravelTranslatable\Relations\Concerns\HasOneOrManyImplementation;

class TranslatedHasMany extends HasMany
{
    use HasOneOrManyImplementation;
}
