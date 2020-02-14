<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasLanguageScopes
{
    /**
     * @var array
     */
    protected static $modelQueryHistory = [];

    /**
     * @var array
     */
    protected $languageQueryStatus = [
        'language_scope_applied' => false,
        'language_scope_disabled' => false,
        'default_language_scope_disabled' => false,
    ];

    /**
     * @param  null  $key
     * @param  null  $value
     * @return array|mixed
     */
    public function languageQueryStatus($key = null, $value = null)
    {
        if ($key && $value !== null) {
            return $this->languageQueryStatus[$key] = $value;
        }

        return $key
            ? Arr::get($this->languageQueryStatus, $key)
            : $this->languageQueryStatus;
    }

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

        $this->languageQueryStatus('language_scope_applied', true);

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutLanguageScope()
    {
        return tap($this)->languageQueryStatus('language_scope_disabled', true);
    }

    /**
     * @return $this
     */
    public function withoutDefaultLanguageScope()
    {
        return tap($this)->languageQueryStatus('default_language_scope_disabled', true);
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param $language
     */
    protected function setQueryLanguageHistory($language)
    {
        static::$modelQueryHistory[get_class($this->getModel())] = $language;
    }

    /**
     * @return mixed
     */
    protected function getQueryLanguageHistory()
    {
        return Arr::get(static::$modelQueryHistory, get_class($this->getModel()));
    }

    /**
     * @return void
     */
    protected function clearQueryLanguageHistory()
    {
        static::$modelQueryHistory[get_class($this->getModel())] = null;
    }
}
