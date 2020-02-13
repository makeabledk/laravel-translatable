<?php

namespace Makeable\LaravelTranslatable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasCurrentLanguage
{
//    public static function bootHasCurrentLanguage()
//    {
//        static::retrieved(function (Model $model) {
//            $model->requestedLanguage = LanguageScope::getLatestRequestedLanguage($model);
//        });
//    }
//
//    /**
//     * @var array|null
//     */
//    public $requestedLanguage;

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

//
//    /**
//     * @return array|null
//     */
//    public static function getRequestedLanguage()
//    {
//        return static::$latestRequestedLanguage;
//    }
//
//    /**
//     * @param  array|null  $languages
//     * @internal
//     */
//    public static function setRequestedLanguage(array $languages = null)
//    {
//        static::$latestRequestedLanguage = $languages;
//    }

//    /**
//     * @param  \Illuminate\Database\Eloquent\Builder  $query
//     */
//    protected function applyCurrentLanguage(Builder $query)
//    {
//        if (($language = static::getCurrentLanguage()) !== null) {
//            $query->language($language);
//        }
//    }
}
