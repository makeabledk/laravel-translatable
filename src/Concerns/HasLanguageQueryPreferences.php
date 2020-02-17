<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Makeable\LaravelTranslatable\Scopes\ApplyLanguageScope;

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
    public static function fetchAllLanguagesWhenNoFilterApplied()
    {
        ApplyLanguageScope::setMode(ApplyLanguageScope::FETCH_ALL_LANGUAGES_BY_DEFAULT);
    }
}
