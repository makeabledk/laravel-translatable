<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\ModelChecker;
use Makeable\LaravelTranslatable\Scopes\LanguageScope;
use Makeable\LaravelTranslatable\Relations\Concerns\RelationQueryHooks;
use Makeable\LaravelTranslatable\Translatable;

trait TranslatedRelation
{
    use HasDefaultLanguage;

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
        if (ModelChecker::checkTranslatable($model)) {
            return $model->getMasterKey();
        }

        if ($keyName !== null) {
            return $model->getAttribute($keyName);
        }

        return $model->getKey();
    }
}