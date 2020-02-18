<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\ModelChecker;

trait TranslatedRelation
{
    use HasBufferedLanguageScopes;

    /**
     * Add the constraints for a relationship count query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceCountQuery(Builder $query, Builder $parentQuery)
    {
        // In QueriesRelationships@withCount method it instantiates a new query rather than
        // building on this one. That means if withoutLanguageScope() was called directly
        // on the relationship it won't be applied unless we manually specify it here.
        if (ModelChecker::checkTranslatable($query->getModel()) && ! $this->languageScopeEnabled()) {
            $query->withoutLanguageScope();
        }

        return parent::getRelationExistenceCountQuery($query, $parentQuery);
    }

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
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getMasterKeyName(Model $model, $keyName = null)
    {
        if ($this->isTranslatableContext($model)) {
            return 'master_key';
        }

        return $keyName ?? $model->getKeyName();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
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
}
