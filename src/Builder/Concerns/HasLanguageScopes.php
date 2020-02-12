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
    protected $pendingLanguage = null;

    /**
     * @var bool
     */
    protected $pendingDefaultLanguage = null;

    /**
     * @return void
     */
    protected function applyLanguageScope()
    {
//        dd(
//            $this->pendingDefaultLanguage,
//            $this->pendingLanguage
//        );

        if ($this->languageScopeEnabled()) {
            LanguageScope::apply($this, $language = $this->pendingLanguage ?? $this->pendingDefaultLanguage);

            $this->setQueryLanguageHistory($language);
        }
    }

    public function languageScopeEnabled()
    {
        return is_array($language = $this->pendingLanguage ?? $this->pendingDefaultLanguage);
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
