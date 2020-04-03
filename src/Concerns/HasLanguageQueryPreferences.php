<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;

trait HasLanguageQueryPreferences
{
    /**
     * @return string|null
     */
    public static function getCurrentLanguage()
    {
        return app()[__CLASS__.'@currentLanguage'] ??
            app()[__TRAIT__.'@globalLanguage'] ??
            null;
    }

    /**
     * @param  string|null  $language
     */
    public static function setLanguage($language)
    {
        app()[__CLASS__.'@currentLanguage'] = $language;
    }

    /**
     * @param  mixed|null  $language
     */
    public static function setGlobalLanguage($language)
    {
        app()[__TRAIT__.'@globalLanguage'] = $language;
    }

    /**
     * Change default behavior of the LanguageScope.
     * When no language scope was applied on the query, we'll fetch
     * all languages just like a normal non-translatable model.
     */
    public static function fetchAllLanguagesByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_ALL_LANGUAGES_BY_DEFAULT);
    }

    /**
     * Change default behavior of the LanguageScope.
     * When no language scope was applied on the query, we'll fetch
     * all languages just like a normal non-translatable model.
     */
    public static function fetchMasterLanguageByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_MASTER_LANGUAGE_BY_DEFAULT);
    }
}
