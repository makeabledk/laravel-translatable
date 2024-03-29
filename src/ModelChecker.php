<?php

namespace Makeable\LaravelTranslatable;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;

class ModelChecker
{
    /**
     * @var array
     */
    protected static $cache = [];

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public static function checkTranslatable(Model $model)
    {
        return static::$cache[get_class($model)] =
            static::$cache[get_class($model)] ??
            array_key_exists(Translatable::class, class_uses_recursive($model));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Makeable\LaravelTranslatable\Translatable  $model
     * @return \Makeable\LaravelTranslatable\Translatable
     *
     * @throws \BadMethodCallException
     */
    public static function ensureTranslatable(Model $model)
    {
        throw_unless(static::checkTranslatable($model), BadMethodCallException::class);

        return $model;
    }
}
