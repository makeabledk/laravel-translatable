<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Builder\Builder;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Builder\ProxiesGetterFunctions;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait TranslatedRelation
{
    use
//        AppliesDefaultLanguage,
        ProxiesGetterFunctions;

    /**
     * @param  array  $models
     * @param  null  $keyName
     * @return array
     */
    protected function getMasterKeys(array $models, $keyName = null)
    {
        return collect($models)->map(function ($model) use ($keyName) {
            return $this->getMasterKey($model, $keyName);
        })->values()->unique(null, true)->sort()->all();
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getMasterKey(Model $model, $keyName = null)
    {
        return $model->getAttribute($this->getMasterKeyName($model, $keyName));
//
//        if (ModelChecker::checkTranslatable($model) && $this->query->languageScopeEnabled) {
//            return $model->getMasterKey();
//        }
//
//        if ($keyName !== null) {
//            return $model->getAttribute($keyName);
//        }
//
//        return $model->getKey();
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getMasterKeyName(Model $model, $keyName = null)
    {
        if (ModelChecker::checkTranslatable($model) && $this->query->languageScopeEnabled) {
            return 'master_key';
        }

        return $keyName ?? $model->getKeyName();
    }

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
//        if ($model &&
//            ModelChecker::checkTranslatable($model) &&
//            $language = $model->requestedLanguage
//        ) {
//
//            if (defined('DUMPNOW')) {
//                dump('setDefaultLanguageFromModelQuery', get_class($model));
//            }
//
//            // Ensure we always default to master
//            $this->setDefaultLanguage($query, array_merge($language, ['*']));
//
//            return;
//        }

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

        $language = $model->requestedLanguage ?? [$model->language_code];

        $this->setDefaultLanguage($query, array_merge($language, ['*']));
//        $this->setDefaultLanguage($query, [$model->language_code, '*']);
    }

    /**
     * Apply a default language scope unless already set by user.
     *
     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
     * @param $language
     */
    public function setDefaultLanguage(Builder $query, $language)
    {
        if ($query->languageScopeEnabled &&
            $query->defaultLanguageScopeEnabled &&
            ModelChecker::checkTranslatable($query->getModel()) &&
            LanguageScope::wasntApplied($query)
        ) {
            LanguageScope::apply($query, $language);
        }
    }
}
