<?php

namespace Makeable\LaravelTranslatable\Facades;

use Illuminate\Support\Facades\Facade;
use Makeable\LaravelTranslatable\TranslationManager;

class Translations extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TranslationManager::class;
    }
}
