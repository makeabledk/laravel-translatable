<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasBufferedLanguageScopes
{
    /**
     * @var bool
     */
    public $pendingLanguage = null;

    /**
     * @var bool
     */
    public $pendingDefaultLanguage = null;

    protected $hasGetterHook = false;

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function language($languages, $fallbackMaster = false)
    {
        $this->pendingLanguage = LanguageScope::getNormalizedLanguages($languages, $fallbackMaster)->values()->toArray();

        return $this->applyLanguageScopeBeforeGetting();
    }

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLanguage($languages, $fallbackMaster = false)
    {
        $this->pendingDefaultLanguage = LanguageScope::getNormalizedLanguages($languages, $fallbackMaster)->values()->toArray();

        return $this->applyLanguageScopeBeforeGetting();
    }

    /**
     * @param string|array $languages
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLanguageUnlessDisabled($languages, $fallbackMaster = false)
    {
        return $this->pendingDefaultLanguage !== false
            ? $this->defaultLanguage($languages, $fallbackMaster)
            : $this;
    }

    /**
     * @return $this
     */
    public function withoutLanguageScope()
    {
        $this->pendingLanguage = false;

        return $this->applyLanguageScopeBeforeGetting();
    }

    /**
     * @return $this
     */
    public function withoutDefaultLanguageScope()
    {
        $this->pendingDefaultLanguage = false;

        return $this->applyLanguageScopeBeforeGetting();
    }

    // _________________________________________________________________________________________________________________

    protected function applyLanguageScopeBeforeGetting()
    {
        if (! $this->hasGetterHook) {
            $this->query->beforeGetting(function () {
                $this->applyRelationLanguageOnQuery();
            }, 100); // ensure run last

            $this->hasGetterHook = true;
        }

        return $this;
    }

    /**
     * @param  null  $query
     * @return mixed
     */
    protected function applyRelationLanguageOnQuery($query = null)
    {
        $query = $query ?? $this->query;

        if (ModelChecker::checkTranslatable($query->getModel())) {
            // Apply the correct language resolved from the relation if was set
            if (is_array($language = $this->pendingLanguage ?? $this->pendingDefaultLanguage)) {
                return $query->language($language);
            }

            if ($this->pendingLanguage === false) {
                return $query->withoutLanguageScope();
            }

            if ($this->pendingDefaultLanguage === false) {
                return $query->withoutDefaultLanguageScope();
            }

            // If no language was set, but also not disabled, the TranslatedEloquentBuilder
            // itself will default to master language
        }
    }

    protected function languageScopeEnabled()
    {
        return $this->pendingLanguage !== false;
    }
}
