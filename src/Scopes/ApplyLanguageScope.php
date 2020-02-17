<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ApplyLanguageScope implements Scope
{
    /**
     * When no language scope was applied on the query, we'll
     * default to only fetch models of master language.
     */
    public const FILTER_TO_MASTER_LANGUAGE_BY_DEFAULT = 1;

    /**
     * When no language scope was applied on the query, we'll fetch
     * all languages just like a normal non-translatable model.
     */
    public const FETCH_ALL_LANGUAGES_BY_DEFAULT = 2;

    /**
     * @var int
     */
    protected static $mode = self::FILTER_TO_MASTER_LANGUAGE_BY_DEFAULT;

    /**
     * @param $mode
     */
    public static function setMode($mode)
    {
        static::$mode = $mode;
    }

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

        // Finally we'll default to only fetch master-language unless
        // this was disabled either globally or on the query itself.
        if (static::$mode === static::FILTER_TO_MASTER_LANGUAGE_BY_DEFAULT) {
            if (! $builder->languageQueryStatus('default_language_scope_disabled')) {
                return $builder->whereNull('master_id');
            }
        }
    }
}
