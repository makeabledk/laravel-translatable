<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Builder\Builder;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait AppliesDefaultLanguage
{
//    use RelationQueryHooks;
//
//    /**
//     * @var bool
//     */
//    protected $languageScopeEnabled = true;
//
//    /**
//     * @var bool
//     */
//    protected $defaultLanguageScopeEnabled = true;
//
//    /**
//     * Disable the language scope entirely, making it work exactly like
//     * a normal non-translatable relation. It will only match on
//     * the actual 'id' and not 'master_id'.
//     *
//     * @return $this
//     */
//    public function withoutLanguageScope()
//    {
//        $this->languageScopeEnabled = false;
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
//        $this->defaultLanguageScopeEnabled = false;
//
//        return $this;
//    }
//
//    /**
//     * Re-enable language scope after being disabled.
//     *
//     * @return $this
//     */
//    public function withLanguageScope()
//    {
//        $this->languageScopeEnabled = true;
//
//        return $this;
//    }
//
//    /**
//     * Re-enable default language scope after being disabled.
//     *
//     * @return $this
//     */
//    public function withDefaultLanguageScope()
//    {
//        $this->defaultLanguageScopeEnabled = true;
//
//        return $this;
//    }

    /**
     * Check what was actually the latest requested language for the model.
     * Only in case we can't retrieve that, we'll default to the
     * language of the current model.
     *
     * This is useful for eager-loaded queries where we wish to persist
     * the same language preferences throughout the entire nested queries.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromModelQuery(Builder $query, Model $model = null)
    {
        if ($model &&
            ModelChecker::checkTranslatable($model) &&
            $language = $model->requestedLanguage
        ) {
            // Ensure we always default to master
            $this->setDefaultLanguage($query, array_merge($language, ['*']));

            return;
        }

        $this->setDefaultLanguageFromModelLanguage($query, $model);
    }

    /**
     * Set the default language to match the parent model.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromModelLanguage(Builder $query, Model $model = null)
    {
        // Sometimes the parent will be an empty instance. In this case
        // we won't set any default language based on that.
        if (! optional($model)->exists) {
            return;
        }

        $this->setDefaultLanguage($query, [$model->language_code, '*']);
    }

    /**
     * Apply a default language scope unless already set by user.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
     * @param $language
     */
    protected function setDefaultLanguage(Builder $query, $language)
    {
        if ($query->languageScopeEnabled &&
            $query->defaultLanguageScopeEnabled &&
            ModelChecker::checkTranslatable($query->getModel()) &&
            LanguageScope::wasntApplied($query)
        ) {
            dump('Applying default language scope');
            LanguageScope::apply($query, $language);
        }
    }
}
