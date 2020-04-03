<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Scopes\LocaleScope;

trait HasLocaleScopes
{
    /**
     * @var array
     */
    protected static $localeQueryHistory = [];

    /**
     * @var array
     */
    protected $localeQueryStatus = [
        'locale_scope_applied' => false,
        'locale_scope_disabled' => false,
        'default_locale_scope_disabled' => false,
    ];

    /**
     * @param  null  $key
     * @param  null  $value
     * @return array|mixed
     */
    public function localeQueryStatus($key = null, $value = null)
    {
        if ($key && $value !== null) {
            return $this->localeQueryStatus[$key] = $value;
        }

        return $key
            ? Arr::get($this->localeQueryStatus, $key)
            : $this->localeQueryStatus;
    }

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function language($languages, $fallbackMaster = false)
    {
        LocaleScope::apply($this, $languages, $fallbackMaster);

        $this->setLocaleQueryHistory(
            LocaleScope::getNormalizedLanguages($languages, $fallbackMaster)->values()->toArray()
        );

        $this->localeQueryStatus('locale_scope_applied', true);

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutLanguageScope()
    {
        return tap($this)->localeQueryStatus('locale_scope_disabled', true);
    }

    /**
     * @return $this
     */
    public function withoutDefaultLanguageScope()
    {
        return tap($this)->localeQueryStatus('default_locale_scope_disabled', true);
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param $language
     */
    protected function setLocaleQueryHistory($language)
    {
        static::$localeQueryHistory[get_class($this->getModel())] = $language;
    }

    /**
     * @return mixed
     */
    protected function getLocaleQueryHistory()
    {
        return Arr::get(static::$localeQueryHistory, get_class($this->getModel()));
    }

    /**
     * @return void
     */
    protected function clearLocaleQueryHistory()
    {
        static::$localeQueryHistory[get_class($this->getModel())] = null;
    }
}
