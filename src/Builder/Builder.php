<?php

namespace Makeable\LaravelTranslatable\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    use QueuesQueries;

    /**
     * @var bool
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
}
