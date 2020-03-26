<?php

namespace Makeable\LaravelTranslatable\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Makeable\LaravelFactory\FactoryServiceProvider;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;

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
        $app->register(FactoryServiceProvider::class);

        // MySQL 5.6 compatibility
        Schema::defaultStringLength(191);
        Config::set('database.connections.mysql.strict', env('DB_STRICT', true));
        DB::reconnect();

        return $app;
    }
}
