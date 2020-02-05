<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Makeable\LaravelTranslatable\Relations\Concerns\BelongsToImplementation;

class TranslatedBelongsTo extends BelongsTo
{
    use BelongsToImplementation;
}
