<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Builder\Concerns\HasGetterHooks;
use Makeable\LaravelTranslatable\Builder\Concerns\ProxyGetterMethods;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;

trait TranslatedRelation
{
    use HasBufferedLanguageScopes;
//        HasGetterHooks;

    //        ProxyGetterMethods;

    /**
     * @param  array  $models
     * @param  null  $keyName
     * @return array
     */
    protected function getMasterKeys(array $models, $keyName = null)
    {
        return collect($models)->map(function ($model) use ($keyName) {
            return $this->getMasterKey($model, $keyName, $model->newQuery());
        })->values()->unique(null, true)->sort()->all();
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getMasterKey(Model $model, $keyName = null, $query = null)
    {
        return $model->getAttribute($this->getMasterKeyName($model, $keyName, $query));
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getMasterKeyName(Model $model, $keyName = null, $query = null)
    {
//        if (ModelChecker::checkTranslatable($model) && $this->queryLanguageScopeEnabled($query ?? $this->query)) {
        if ($this->isTranslatableContext($model)) {
            return 'master_key';
        }

        return $keyName ?? $model->getKeyName();
    }

    protected function isTranslatableContext(Model $model)
    {
        return ModelChecker::checkTranslatable($model) && $this->languageScopeEnabled();
    }

    /**
     * Check what was actually the latest requested language for the model.
     * Only in case we can't retrieve that, we'll default to the
     * language of the current model.
     *
     * This is useful for eager-loaded queries where we wish to persist
     * the same language preferences throughout the entire nested queries.
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return $this
     */
    protected function setDefaultLanguageFromModel(Model $model = null)
    {
        // Sometimes the parent will be an empty instance or null. In this
        // case we won't attempt to set any default language based on that.
        if (! optional($model)->exists) {
            return $this;
        }
//
//        dump(
//            $model->getMorphClass(),
//            $model->language_code
//        );

        // Before we attemt to set the language from the child / parent model,
        // we'll first check if the related model already has language
        // preference set directly through HasCurrentLanguage::class.
        if (ModelChecker::checkTranslatable($this->related)) {
            if ($language = call_user_func([get_class($this->related), 'getCurrentLanguage'])) {
                return $this->defaultLanguageUnlessDisabled($language, true);
            }
        }

        // The model represents the child or parent from which we're loading the relation.
        // The related model can still be translatable, but in this case it does not
        // make sense to try and set the language from a non-translatable model.
        if (ModelChecker::checkTranslatable($model)) {
            $language = $model->requestedLanguage ?? [$model->language_code];

            return $this->defaultLanguageUnlessDisabled($language, true);
        }

        return $this;
    }

//
//    /**
//     * Check what was actually the latest requested language for the model.
//     * Only in case we can't retrieve that, we'll default to the
//     * language of the current model.
//     *
//     * This is useful for eager-loaded queries where we wish to persist
//     * the same language preferences throughout the entire nested queries.
//     *
//     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
//     * @param  \Illuminate\Database\Eloquent\Model|null  $model
//     * @return void
//     */
//    protected function setDefaultLanguageFromModelQuery(Builder $query, Model $model = null)
//    {
////        if ($model &&
////            ModelChecker::checkTranslatable($model) &&
////            $language = $model->requestedLanguage
////        ) {
////
////            if (defined('DUMPNOW')) {
////                dump('setDefaultLanguageFromModelQuery', get_class($model));
////            }
////
////            // Ensure we always default to master
////            $this->setDefaultLanguage($query, array_merge($language, ['*']));
////
////            return;
////        }
//
//        $this->setDefaultLanguageFromModelLanguage($query, $model);
//    }
//
//    /**
//     * Set the default language to match the parent model.
//     *
//     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
//     * @param  \Illuminate\Database\Eloquent\Model|null  $model
//     * @return void
//     */
//    protected function setDefaultLanguageFromModelLanguage(Builder $query, Model $model = null)
//    {
//        // Sometimes the parent will be an empty instance. In this case
//        // we won't set any default language based on that.
//        if (! optional($model)->exists) {
//            return;
//        }
//
//        $language = $model->requestedLanguage ?? [$model->language_code];
//        $language = array_merge($language, ['*']);
//
//        $this->defaultLanguageUnlessDisabled($language);
//
//        return $this;
//
////        $this->setDefaultLanguage($query, [$model->language_code, '*']);
//    }

//    /**
//     * Apply a default language scope unless already set by user.
//     *
//     * @param  \Makeable\LaravelTranslatable\Builder\Builder  $query
//     * @param $language
//     */
//    public function setDefaultLanguage(Builder $query, $language)
//    {
//        if (
////            $query->languageScopeEnabled &&
////            $query->defaultLanguageScopeEnabled &&
//            ModelChecker::checkTranslatable($query->getModel()) &&
//            LanguageScope::wasntApplied($query)
//        ) {
//            LanguageScope::apply($query, $language);
//        }
//    }
}
