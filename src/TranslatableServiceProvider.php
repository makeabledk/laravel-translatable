<?php

namespace Makeable\LaravelTranslatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->scoped(TranslationManager::class);
    }
}
