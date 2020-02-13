<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasLanguageScopes
{
    protected static $modelQueryHistory = [];

    public $languageScopeWasApplied = false;

    public $languageScopeWasDisabled = false;

    public $defaultLanguageScopeWasDisabled = false;

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function language($languages, $fallbackMaster = false)
    {
        LanguageScope::apply($this, $languages, $fallbackMaster);

        $this->setQueryLanguageHistory(
            LanguageScope::getNormalizedLanguages($languages, $fallbackMaster)->values()->toArray()
        );

        $this->languageScopeWasApplied = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutLanguageScope()
    {
        $this->languageScopeWasDisabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutDefaultLanguageScope()
    {
        $this->defaultLanguageScopeWasDisabled = true;

        return $this;
    }

//
//    /**
//     * Merge the where constraints from another query to the current query.
//     *
//     * @param  \Illuminate\Database\Eloquent\Builder  $from
//     * @return \Illuminate\Database\Eloquent\Builder|static
//     */
//    public function mergeConstraintsFrom(Builder $from)
//    {
//        $from->invokeBeforeGettingCallbacks();
//
//        $this->languageScopeWasApplied = $from->languageScopeWasApplied;
//        $this->languageScopeWasDisabled = $from->languageScopeWasDisabled;
//        $this->defaultLanguageScopeWasDisabled = $from->defaultLanguageScopeWasDisabled;
//
//        return parent::mergeConstraintsFrom($from);
//    }


    protected function setQueryLanguageHistory($language)
    {
        static::$modelQueryHistory[get_class($this->getModel())] = $language;
    }

    protected function getQueryLanguageHistory()
    {
        return Arr::get(static::$modelQueryHistory, get_class($this->getModel()));
    }

    protected function clearQueryLanguageHistory()
    {
        static::$modelQueryHistory[get_class($this->getModel())] = null;
    }
}
