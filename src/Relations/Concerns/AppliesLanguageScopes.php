<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait AppliesLanguageScopes
{
    use RelationQueryHooks;

    /**
     * @var bool
     */
    protected $applyLanguageScope = true;

    /**
     * @var bool
     */
    protected $applyDefaultLanguageScope = true;

    /**
     * Disable the language scope entirely, making it work exactly like
     * a normal non-translatable relation. It will only match on
     * the actual 'id' and not 'master_id'.
     *
     * @return $this
     */
    public function withoutLanguageScope()
    {
        $this->applyLanguageScope = false;

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
        $this->applyDefaultLanguageScope = false;

        return $this;
    }

    /**
     * Re-enable language scope after being disabled.
     *
     * @return $this
     */
    public function withLanguageScope()
    {
        $this->applyLanguageScope = true;

        return $this;
    }

    /**
     * Re-enable default language scope after being disabled.
     *
     * @return $this
     */
    public function withDefaultLanguageScope()
    {
        $this->applyDefaultLanguageScope = true;

        return $this;
    }

    /**
     * Check what was actually the latest requested language for the model.
     * Only in case we can't retrieve that, we'll default to the
     * language of the current model.
     *
     * This is useful for eager-loaded queries where we wish to persist
     * the same language preferences throughout the entire nested queries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromLatestQuery(Builder $query, Model $model = null)
    {
        if ($model &&
            ModelChecker::checkTranslatable($model) &&
            $language = LanguageScope::getLatestRequestedLanguage($model)
        ) {
            // Ensure we always default to master
            $this->setDefaultLanguage($query, array_merge($language, ['*']));

            return;
        }

        $this->setDefaultLanguageFromModel($query, $model);
    }

    /**
     * Set the default language to match the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return void
     */
    protected function setDefaultLanguageFromModel(Builder $query, Model $model = null)
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param $language
     */
    protected function setDefaultLanguage(Builder $query, $language)
    {
        if ($this->applyLanguageScope &&
            $this->applyDefaultLanguageScope &&
            ModelChecker::checkTranslatable($query->getModel()) &&
            LanguageScope::wasntApplied($query)
        ) {
            LanguageScope::apply($query, $language);
        }
    }

//
//    /**
//     * @param callable $callable
//     */
//    protected function whenLanguageScopeEnabled($callable)
//    {
//        $this->beforeGetting(function (Builder $query) use ($callable) {
//            if ($this->applyLanguageScope) {
//                call_user_func($callable, $query);
//            }
//        });
//    }
}
