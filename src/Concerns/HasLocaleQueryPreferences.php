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
     */
    public static function setLocale($locale)
    {
        app()[__CLASS__.'@currentLocale'] = $locale;
    }

    /**
     * @param  mixed|null  $locale
     */
    public static function setGlobalLocale($locale)
    {
        app()[__TRAIT__.'@globalLocale'] = $locale;
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, we'll fetch
     * all locales just like a normal non-translatable model.
     */
    public static function fetchAllLocalesByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_ALL_LOCALES_BY_DEFAULT);
    }

    /**
     * Change default behavior of the LocaleScope.
     * When no locale scope was applied on the query, we'll fetch
     * all locales just like a normal non-translatable model.
     */
    public static function fetchMasterLocaleByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_MASTER_LOCALE_BY_DEFAULT);
    }
}
