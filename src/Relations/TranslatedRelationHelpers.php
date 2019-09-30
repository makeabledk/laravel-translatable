<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Translatable;

trait TranslatedRelationHelpers
{
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
        if ($this->modelIsTranslatable($model)) {
            return $model->getMasterKey();
        }

        if ($keyName !== null) {
            return $model->getAttribute($keyName);
        }

        return $model->getKey();
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function modelIsTranslatable($model)
    {
        return array_key_exists(Translatable::class, class_uses($model));
    }
}