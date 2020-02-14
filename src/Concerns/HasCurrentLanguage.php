<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasCurrentLanguage
{
    /**
     * @var string|null
     */
    protected static $currentLanguage;

    /**
     * @return string|null
     */
    public static function getCurrentLanguage()
    {
        return static::$currentLanguage ?? app()[__TRAIT__.'@globalLanguage'] ?? null;
    }

    /**
     * @param  string|null  $language
     */
    public static function setLanguage($language)
    {
        static::$currentLanguage = $language;
    }

    /**
     * @param  mixed|null  $language
     */
    public static function setGlobalLanguage($language)
    {
        app()[__TRAIT__.'@globalLanguage'] = $language;
    }
}
