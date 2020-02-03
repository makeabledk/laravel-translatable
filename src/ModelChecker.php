<?php

namespace Makeable\LaravelTranslatable;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;

class ModelChecker
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public static function checkTranslatable(Model $model)
    {
        return array_key_exists(Translatable::class, class_uses_recursive($model));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Makeable\LaravelTranslatable\Translatable  $model
     * @throws \BadMethodCallException
     * @return \Makeable\LaravelTranslatable\Translatable
     */
    public static function ensureTranslatable(Model $model)
    {
        throw_unless(static::checkTranslatable($model), BadMethodCallException::class);

        return $model;
    }
}
