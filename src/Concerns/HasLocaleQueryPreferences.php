<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Makeable\LaravelTranslatable\Facades\Translations;

trait HasLocaleQueryPreferences
{
    /**
     * @return string|null
     */
    public static function getCurrentLocale()
    {
        return Translations::getCurrentLocale(static::class);
    }

    /**
     * @param  string|null  $locale
     * @param  callable|null  $callback
     * @return mixed|void
     */
    public static function setLocale($locale, ?callable $callback = null)
    {
        return Translations::setLocale(static::class, $locale, $callback);
    }

    /**
     * @param  string|null  $locale
     * @param  callable|null  $callback
     * @return mixed|void
     *
     * @deprecated use Translations facade instead to manage global state
     */
    public static function setGlobalLocale($locale, ?callable $callback = null)
    {
        return Translations::setGlobalLocale($locale, $callback);
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, all locales will
     * be fetched just like a normal non-translatable model.
     *
     * @deprecated use Translations facade instead to manage global state
     */
    public static function fetchAllLocalesByDefault()
    {
        Translations::fetchAllLocalesByDefault();
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, only the master
     * version of the model will be fetched from the database (DEFAULT).
     *
     * @deprecated use Translations facade instead to manage global state
     */
    public static function fetchMasterLocaleByDefault()
    {
        Translations::fetchMasterLocaleByDefault();
    }
}
