<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Makeable\LaravelTranslatable\Relations\Concerns\MorphOneOrManyImplementation;

class TranslatedMorphMany extends MorphMany
{
    use MorphOneOrManyImplementation;
}
