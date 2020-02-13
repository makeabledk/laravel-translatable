<?php

namespace Makeable\LaravelTranslatable\Builder\Concerns;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Relations\Concerns\HasBufferedLanguageScopes;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait HasLanguageScopes
{
    protected static $modelQueryHistory = [];

    protected $languageScopeWasApplied = false;

    protected $languageScopeWasDisabled = false;

//    /**
//     * @var bool
//     */
//    public $pendingLanguage = null;
//
//    /**
//     * @var bool
//     */
//    public $pendingDefaultLanguage = null;

//    /**
//     * @return void
//     */
//    protected function applyLanguageScope()
//    {
//        if (is_array($language = $this->getQueryLanguage())) {
//            LanguageScope::apply($this, $language);
//
//            $this->setQueryLanguageHistory($language);
//        }
//    }

//    public function getQueryLanguage()
//    {
//        return $this->pendingLanguage ?? $this->pendingDefaultLanguage;
//    }
//
//    public function languageScopeEnabled()
//    {
//        return $this->pendingLanguage !== false;
//    }

    protected function applyCurrentLanguageWhenApplicable()
    {
        if (! $this->languageScopeWasDisabled && ! $this->languageScopeWasApplied) {
            if ($language = call_user_func([get_class($this->getModel()), 'getCurrentLanguage'])) {
                $this->language($language);
            }
        }
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
//        $this->pendingLanguage = $this->getNormalizedLanguage($languages, $fallbackMaster);

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


//
//    /**
//     * @param string|array $languages
//     * @param bool $fallbackMaster
//     * @return $this
//     */
//    public function defaultLanguageUnlessDisabled($languages, $fallbackMaster = false)
//    {
//        if ($this->pendingDefaultLanguage !== false) {
//            $this->defaultLanguage($languages, $fallbackMaster);
//        }
//
//        return $this;
//    }
//
//    /**
//     * @param string|array $languages
//     * @param bool $fallbackMaster
//     * @return $this
//     */
//    public function defaultLanguage($languages, $fallbackMaster = false)
//    {
//        $this->pendingDefaultLanguage = LanguageScope::getNormalizedLanguages($languages, $fallbackMaster);
//
//
//        return $this;
//    }
//
//    /**
//     * Disable the language scope entirely, making it work exactly like
//     * a normal non-translatable relation.
//     *
//     * @return $this
//     * @deprecated
//     */
//    public function withoutLanguageScope()
//    {
//        $this->pendingLanguage = false;
//
//        return $this;
//    }
//
//    /**
//     * Fetch all related models in relationship including translations.
//     * Standard behavior is that it only fetches the best matching
//     * version to the current language of the parent.
//     *
//     * @return $this
//     */
//    public function withoutDefaultLanguageScope()
//    {
//        $this->pendingDefaultLanguage = false;
//
//        return $this;
//    }
}
