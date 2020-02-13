<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Builder\Concerns\HasGetterHooks;
use Makeable\LaravelTranslatable\Builder\TranslatableEloquentBuilder;
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

//    abstract public function beforeGetting(callable $callback, $priority = null);


    public function getQueryLanguage()
    {
        return $this->pendingLanguage ?? $this->pendingDefaultLanguage;
    }

    public function languageScopeEnabled()
    {
        return $this->pendingLanguage !== false;
    }

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
        if ($this->pendingDefaultLanguage !== false) {
            $this->defaultLanguage($languages, $fallbackMaster);
        }

        return $this;
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

    /**
     * @return void
     */
    protected function applyLanguageScope($query = null)
    {
        $query = $query ?? $this->query;

        if (is_array($language = $this->getQueryLanguage()) && $query instanceof TranslatableEloquentBuilder) {
            $query->language($language);
        }
    }

    protected function applyLanguageScopeBeforeGetting()
    {
        if (! $this->hasGetterHook) {
            $this->query->beforeGetting(function () {
                $this->applyLanguageScope();
            }, 100); // ensure run last

            $this->hasGetterHook = true;
        }

        return $this;
    }
}
