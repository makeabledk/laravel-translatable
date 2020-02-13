<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

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
