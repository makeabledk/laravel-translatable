<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasLanguageScopes
{
    protected static $modelQueryHistory = [];

    /**
     * @var bool
     */
    public $pendingLanguage = null;

    /**
     * @var bool
     */
    public $pendingDefaultLanguage = null;

    /**
     * @return void
     */
    protected function applyLanguageScope()
    {
//        dd(
//            $this->pendingDefaultLanguage,
//            $this->pendingLanguage
//        );
//
//        if (defined('OK_TEST')) {
//            dd($this->pendingLanguage, $this->pendingDefaultLanguage);
//        }

        if (is_array($language = $this->getQueryLanguage())) {
            LanguageScope::apply($this, $language);

            $this->setQueryLanguageHistory($language);
        }
    }

    public function getQueryLanguage()
    {
        return $this->pendingLanguage ?? $this->pendingDefaultLanguage;
    }

    public function languageScopeEnabled()
    {
        return $this->pendingLanguage !== false;
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

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function language($languages, $fallbackMaster = false)
    {
        $this->pendingLanguage = $this->getNormalizedLanguage($languages, $fallbackMaster);

        return $this;
    }

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLanguageUnlessDisabled($languages, $fallbackMaster = false)
    {
        if ($this->pendingDefaultLanguage !== false) {
            $this->defaultLanguage($languages, $fallbackMaster);
        }

        return $this;
    }

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLanguage($languages, $fallbackMaster = false)
    {
        $this->pendingDefaultLanguage = $this->getNormalizedLanguage($languages, $fallbackMaster);

        return $this;
    }

    protected function getNormalizedLanguage($languages, $fallbackMaster = false)
    {
        $languages = Arr::wrap($languages);

        if ($fallbackMaster) {
            $languages[] = '*';
        }

        return $languages;
    }

    /**
     * Disable the language scope entirely, making it work exactly like
     * a normal non-translatable relation.
     *
     * @return $this
     * @deprecated
     */
    public function withoutLanguageScope()
    {
        $this->pendingLanguage = false;

        return $this;
    }

//    /**
//     * Re-enable default language scope after being disabled.
//     *
//     * @return $this
//     */
//    public function withDefaultLanguageScope()
//    {
//        $this->pendingDefaultLanguage = null;
//
//        return $this;
//    }

    /**
     * Fetch all related models in relationship including translations.
     * Standard behavior is that it only fetches the best matching
     * version to the current language of the parent.
     *
     * @return $this
     */
    public function withoutDefaultLanguageScope()
    {
        $this->pendingDefaultLanguage = false;

        return $this;
    }
}
