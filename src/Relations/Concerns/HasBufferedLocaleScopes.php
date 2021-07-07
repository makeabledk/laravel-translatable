<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\ApplyLocaleScope;
use Makeable\LaravelTranslatable\Scopes\LocaleScope;

trait HasBufferedLocaleScopes
{
    /**
     * @var bool
     */
    public $pendingLocale = null;

    /**
     * @var bool
     */
    public $pendingDefaultLocale = null;

    /**
     * @var bool
     */
    protected $hasGetterHook = false;

    /**
     * @param string|array $locales
     * @param bool $fallbackMaster
     * @return $this
     */
    public function locale($locales, $fallbackMaster = false)
    {
        $this->pendingLocale = LocaleScope::getNormalizedLocales($locales, $fallbackMaster)->toArray();

        return $this->applyLocaleScopeBeforeGetting();
    }

    /**
     * @param string|array $locales
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLocale($locales, $fallbackMaster = false)
    {
        $this->pendingDefaultLocale = LocaleScope::getNormalizedLocales($locales, $fallbackMaster)->toArray();

        return $this->applyLocaleScopeBeforeGetting();
    }

    /**
     * @param string|array $locales
     * @param bool $fallbackMaster
     * @return $this
     */
    public function defaultLocaleUnlessDisabled($locales, $fallbackMaster = false)
    {
        if ($this->pendingDefaultLocale === false || ApplyLocaleScope::modeIs(ApplyLocaleScope::FETCH_ALL_LOCALES_BY_DEFAULT)) {
            return $this;
        }

        return $this->defaultLocale($locales, $fallbackMaster);
    }

    /**
     * @return $this
     */
    public function withoutLocaleScope()
    {
        $this->pendingLocale = false;

        return $this->applyLocaleScopeBeforeGetting();
    }

    /**
     * @return $this
     */
    public function withoutDefaultLocaleScope()
    {
        $this->pendingDefaultLocale = false;

        return $this->applyLocaleScopeBeforeGetting();
    }

    // _________________________________________________________________________________________________________________

    /**
     * @return $this
     */
    protected function applyLocaleScopeBeforeGetting()
    {
        if (! $this->hasGetterHook) {
            $this->query->beforeGetting(function () {
                $this->applyRelationLocaleOnQuery();
            }, 100); // ensure run after relational constraints are applied

            $this->hasGetterHook = true;
        }

        return $this;
    }

    /**
     * @param  null  $query
     * @return mixed
     */
    protected function applyRelationLocaleOnQuery($query = null)
    {
        $query = $query ?? $this->query;

        if (ModelChecker::checkTranslatable($query->getModel())) {
            // Apply the correct locale resolved from the relation if was set
            if (is_array($locale = $this->pendingLocale ?? $this->pendingDefaultLocale)) {
                return $query->locale($locale);
            }

            if ($this->pendingLocale === false) {
                return $query->withoutLocaleScope();
            }

            if ($this->pendingDefaultLocale === false) {
                return $query->withoutDefaultLocaleScope();
            }

            // When no preferences were set whatsoever, ApplyLocaleScope will
            // will default to only fetch master locale.
        }
    }

    /**
     * @return bool
     */
    protected function localeScopeEnabled()
    {
        return $this->pendingLocale !== false;
    }
}
