<?php

namespace Makeable\LaravelTranslatable\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelTranslatable\Queries\BestLanguageQuery;
use Makeable\LaravelTranslatable\Translatable;

trait TranslatedRelation
{
    use RelationQueryHooks;

//
//    /**
//     * Constrain the relation query to a specific language. This is the same implementation
//     * as Translatable@scopeLanguage, however, having it on the relation itself allows
//     * us to customize implementation for each relation type.
//     *
//     * @param string|array $languagePriority
//     * @param bool $fallbackMaster
//     * @return Builder
//     */
//    public function language($languagePriority, $fallbackMaster = false)
//    {
//        return call_user_func(new BestLanguageQuery, $this->query, ...func_get_args());
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