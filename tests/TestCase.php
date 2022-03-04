<?php

namespace Makeable\LaravelTranslatable\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Makeable\LaravelFactory\Factory;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=mysql');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->useEnvironmentPath(__DIR__.'/..');
        $app->useDatabasePath(__DIR__);
        $app->make(Kernel::class)->bootstrap();

        Factory::guessFactoryNamesUsing(function ($model) {
            return '\\Makeable\\LaravelTranslatable\\Tests\\Factories\\'.class_basename($model);
        });

        return $app;
    }
}
