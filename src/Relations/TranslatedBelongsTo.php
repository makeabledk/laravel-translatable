<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedBelongsToConstraints;
use Makeable\LaravelTranslatable\Relations\Concerns\BelongsToImplementation;
use Makeable\LaravelTranslatable\Relations\Concerns\TranslatedRelation;
use Illuminate\Database\Eloquent\Builder;

class TranslatedBelongsTo extends BelongsTo
{
    use BelongsToImplementation;
//        TranslatedBelongsToConstraints;

}
