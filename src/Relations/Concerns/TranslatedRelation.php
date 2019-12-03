<?php

namespace Makeable\LaravelTranslatable\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Builder\TranslatableBuilder;
use Makeable\LaravelTranslatable\ModelChecker;

trait TranslatedRelation
{
    use AppliesDefaultLanguage;
//
//    protected function beforeGetting(callable $callable)
//    {
//        if ($this->query instanceof TranslatableBuilder) {
//            return $this->__call('beforeGetting', [$callable]);
//        }
//
//        return $this->tap($callable);
//    }

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
        if (ModelChecker::checkTranslatable($model) && $this->query->languageScopeEnabled) {
            return $model->getMasterKey();
        }

        if ($keyName !== null) {
            return $model->getAttribute($keyName);
        }

        return $model->getKey();
    }
}
