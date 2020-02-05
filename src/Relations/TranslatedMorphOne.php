<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Makeable\LaravelTranslatable\Relations\Concerns\MorphOneOrManyImplementation;

class TranslatedMorphOne extends MorphOne
{
    use MorphOneOrManyImplementation;
}
