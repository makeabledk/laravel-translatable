<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\TranslatableField;

trait TranslatedRelation
{
    use HasBufferedLocaleScopes;

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
        // building on this one. That means if withoutLocaleScope() was called directly
        // on the relationship it won't be applied unless we manually specify it here.
        if (ModelChecker::checkTranslatable($query->getModel()) && ! $this->localeScopeEnabled()) {
            $query->withoutLocaleScope();
        }

        return parent::getRelationExistenceCountQuery($query, $parentQuery);
    }

    /**
     * @param  array  $models
     * @param  null  $keyName
     * @return array
     */
    protected function getModelKeys(array $models, $keyName = null)
    {
        return collect($models)->map(function ($model) use ($keyName) {
            return $this->getModelKey($model, $keyName);
        })->values()->unique(null, true)->sort()->all();
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getModelKey(Model $model, $keyName = null)
    {
        return $model->getAttribute($this->getModelKeyName($model, $keyName));
    }

    /**
     * @param Model $model
     * @param null $keyName
     * @return mixed
     */
    protected function getModelKeyName(Model $model, $keyName = null)
    {
        if ($this->isTranslatableContext($model)) {
            return TranslatableField::$sibling_id;
        }

        return $keyName ?? $model->getKeyName();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function isTranslatableContext(Model $model)
    {
        return ModelChecker::checkTranslatable($model) && $this->localeScopeEnabled();
    }

    /**
     * Check what was actually the latest requested locale for the model.
     * Only in case we can't retrieve that, we'll default to the
     * locale of the current model.
     *
     * This is useful for eager-loaded queries where we wish to persist
     * the same locale preferences throughout the entire nested queries.
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return $this
     */
    protected function setDefaultLocaleFromModel(Model $model = null)
    {
        // Sometimes the parent will be an empty instance or null. In this
        // case we won't attempt to set any default locale based on that.
        if (! optional($model)->exists) {
            return $this;
        }

        // Before we attempt to set the locale from the child / parent model,
        // we'll first check if the related model already has locale
        // preference set directly through HasCurrentLocale::class.
        if (ModelChecker::checkTranslatable($this->related)) {
            if ($locale = call_user_func([get_class($this->related), 'getCurrentLocale'])) {
                return $this->defaultLocaleUnlessDisabled($locale, true);
            }
        }

        // The model represents the child or parent from which we're loading the relation.
        // The related model can still be translatable, but in this case it does not
        // make sense to try and set the locale from a non-translatable model.
        if (ModelChecker::checkTranslatable($model)) {
            $locale = $model->requestedLocale ?? [$model->getAttribute(TranslatableField::$locale)];

            return $this->defaultLocaleUnlessDisabled($locale, true);
        }

        return $this;
    }
}
