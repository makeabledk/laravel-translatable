<?php

namespace Makeable\LaravelTranslatable\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Makeable\LaravelTranslatable\TranslatableField;

class ApplyLocaleScope implements Scope
{
    /**
     * When no locale scope was applied on the query, we'll
     * default to only fetch models of master locale.
     */
    public const FETCH_MASTER_LOCALE_BY_DEFAULT = 1;

    /**
     * When no locale scope was applied on the query, we'll fetch models
     * of all locales just like a normal non-translatable model.
     */
    public const FETCH_ALL_LOCALES_BY_DEFAULT = 2;

    /**
     * @var int
     */
    protected static $mode = self::FETCH_MASTER_LOCALE_BY_DEFAULT;

    /**
     * @param string $mode
     */
    public static function setMode($mode)
    {
        static::$mode = $mode;
    }

    /**
     * @param string $mode
     * @return bool
     */
    public static function modeIs($mode)
    {
        return static::$mode === $mode;
    }

    /**
     * This scope will be applied as the very last thing before executing the query.
     * We'll check whether or not we'll need to add more locale constrains.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\EloquentBuilder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function apply(Builder $builder, Model $model)
    {
        // If locale scope was already applied or disabled we won't do anything.
        if ($builder->localeQueryStatus('locale_scope_applied') ||
            $builder->localeQueryStatus('locale_scope_disabled')) {
            return $builder;
        }

        // If a current locale was set we'll apply that.
        if ($locale = call_user_func([get_class($builder->getModel()), 'getCurrentLocale'])) {
            return $builder->locale($locale);
        }

        // Finally we'll default to only fetch master-locale unless
        // this was disabled either globally or on the query itself.
        if (static::$mode === static::FETCH_MASTER_LOCALE_BY_DEFAULT) {
            if (! $builder->localeQueryStatus('default_locale_scope_disabled')) {
                return $builder->whereNull(TranslatableField::$master_id);
            }
        }
    }
}
