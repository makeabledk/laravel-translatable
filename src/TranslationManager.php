<?php

namespace Makeable\LaravelTranslatable;

use Makeable\LaravelTranslatable\Concerns\HasLocaleQueryPreferences;
use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;

class TranslationManager
{
    protected const GLOBAL_LOCALE_KEY = HasLocaleQueryPreferences::class.'@globalLocale';

    public function getCurrentLocale(?string $modelClass = null)
    {
        return ($modelClass ? app()[$this->currentLocaleKey($modelClass)] ?? null : null)
            ?? $this->getGlobalLocale();
    }

    public function getGlobalLocale()
    {
        return app()[self::GLOBAL_LOCALE_KEY] ?? null;
    }

    public function setLocale(string $modelClass, $locale, ?callable $callback = null)
    {
        return $this->setContainerValue($this->currentLocaleKey($modelClass), $locale, $callback);
    }

    public function setGlobalLocale($locale, ?callable $callback = null)
    {
        return $this->setContainerValue(self::GLOBAL_LOCALE_KEY, $locale, $callback);
    }

    public function usingLocale($locale, callable $callback)
    {
        return $this->setGlobalLocale($locale, $callback);
    }

    public function fetchAllLocalesByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_ALL_LOCALES_BY_DEFAULT);
    }

    public function fetchMasterLocaleByDefault()
    {
        ApplyLocaleScope::setMode(ApplyLocaleScope::FETCH_MASTER_LOCALE_BY_DEFAULT);
    }

    protected function currentLocaleKey(string $modelClass): string
    {
        return $modelClass.'@currentLocale';
    }

    protected function setContainerValue(string $key, $value, ?callable $callback = null)
    {
        $previous = app()[$key] ?? null;

        app()[$key] = $value;

        if (! $callback) {
            return null;
        }

        try {
            return $callback();
        } finally {
            app()[$key] = $previous;
        }
    }
}
