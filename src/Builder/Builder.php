<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    use QueuesQueries;

    /**
     * @var bool
     * @deprecated
     */
    public $languageScopeEnabled = true;

    /**
     * @var bool
     */
    public $defaultLanguageScopeEnabled = true;

    /**
     * @var bool
     */
    public $languageScopeWasApplied = false;

    /**
     * Re-enable language scope after being disabled.
     *
     * @return $this
     * @deprecated
     */
    public function withLanguageScope()
    {
        $this->languageScopeEnabled = true;

        return $this;
    }

    /**
     * Disable the language scope entirely, making it work exactly like
     * a normal non-translatable relation. It will only match on
     * the actual 'id' and not 'master_id'.
     *
     * @return $this
     * @deprecated
     */
    public function withoutLanguageScope()
    {
        $this->languageScopeEnabled = false;

        return $this;
    }

    /**
     * Re-enable default language scope after being disabled.
     *
     * @return $this
     */
    public function withDefaultLanguageScope()
    {
        $this->defaultLanguageScopeEnabled = true;

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
        $this->defaultLanguageScopeEnabled = false;

        return $this;
    }

    /**
     * Dump a stack trace at given point in query.
     */
    public function getTrace()
    {
        dd($this->normalizeStackTrace(debug_backtrace()));
    }

    /**
     * @param $stack
     * @return array|string
     */
    protected function normalizeStackTrace($stack)
    {
        if (is_object($stack)) {
            return get_class($stack);
        }

        if (is_array($stack)) {
            return array_map(\Closure::fromCallable([$this, 'normalizeStackTrace']), $stack);
        }

        return $stack;
    }
}