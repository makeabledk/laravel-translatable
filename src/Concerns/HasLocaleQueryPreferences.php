<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;

trait HasLocaleQueryPreferences
{
    /**
     * @return string|null
     */
    public static function getCurrentLocale()
    {
        return app()[__CLASS__.'@currentLocale'] ??
            app()[__TRAIT__.'@globalLocale'] ??
            null;
    }

    /**
     * @param  string|null  $locale
     * @param  callable|null  $callback
     * @return mixed|void
     */
    public static function setLocale($locale, ?callable $callback = null)
    {
        return static::handleSetLocale(__CLASS__.'@currentLocale', $locale, $callback);
    }

    /**
     * @param  string|null  $locale
     * @param  callable|null  $callback
     * @return mixed|void
     */
    public static function setGlobalLocale($locale, ?callable $callback = null)
    {
        return static::handleSetLocale(__TRAIT__.'@globalLocale', $locale, $callback);
    }

    protected static function handleSetLocale($containerKey, $locale, ?callable $callback = null)
    {
        $previous = app()[$containerKey] ?? null;
        $reset = function () use ($containerKey, $previous) {
            static::handleSetLocale($containerKey, $previous);
        };

        app()[$containerKey] = $locale;

        if ($callback) {
            try {
                return tap(call_user_func($callback), $reset);
            } catch (\Exception $exception) {
                $reset();
                throw $exception;
            }
        }
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, all locales will
     * be fetched just like a normal non-translatable model.
     */
    public static function fetchAllLocalesByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_ALL_LOCALES_BY_DEFAULT);
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, only the master
     * version of the model will be fetched from the database (DEFAULT).
     */
    public static function fetchMasterLocaleByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_MASTER_LOCALE_BY_DEFAULT);
    }
}
