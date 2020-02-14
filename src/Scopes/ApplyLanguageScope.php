<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ApplyLanguageScope implements Scope
{
    /**
     * This scope will be applied as the very last thing before executing the query.
     * We'll check whether or not we'll need to add more language constrains.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function apply(Builder $builder, Model $model)
    {
        // If language scope was already applied or disabled we won't do anything.
        if ($builder->languageQueryStatus('language_scope_applied') ||
            $builder->languageQueryStatus('language_scope_disabled')) {
            return $builder;
        }

        // If a current language was set we'll apply that.
        if ($language = call_user_func([get_class($builder->getModel()), 'getCurrentLanguage'])) {
            return $builder->language($language);
        }

        // Finally we'll default to only fetch master-language unless this was disabled.
        if (! $builder->languageQueryStatus('default_language_scope_disabled')) {
            return $builder->whereNull('master_id');
        }
    }
}
