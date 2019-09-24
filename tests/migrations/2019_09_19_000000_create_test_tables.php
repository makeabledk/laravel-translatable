<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelTranslatable\Tests\Stubs\Category;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('author_id')->nullable();
            $table->unsignedInteger('post_id')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('master_id')->nullable();
            $table->string('language_code');
            $table->timestamps();
        });
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('src')->nullable();
            $table->timestamps();
        });
        Schema::create('category_post', function (Blueprint $table) {
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('post_id');
        });
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('master_id')->nullable();
            $table->string('language_code');
            $table->unsignedInteger('author_id')->nullable();
            $table->unsignedInteger('team_id')->nullable();
            $table->timestamps();
        });
        Schema::create('image_post', function (Blueprint $table) {
            $table->unsignedInteger('image_id');
            $table->unsignedInteger('post_id');
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('taggable');
            $table->timestamps();
        });
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
        Schema::create('team_user', function (Blueprint $table) {
            $table->unsignedInteger('team_id');
            $table->unsignedInteger('user_id');
        });
    }
}