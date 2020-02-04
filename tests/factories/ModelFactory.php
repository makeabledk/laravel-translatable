<?php

use Makeable\LaravelFactory\Factory;
use Makeable\LaravelTranslatable\Tests\Stubs\Category;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;

new class($factory) {
    /**
     * @var Factory
     */
    protected $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;

        $this->multilingual(Category::class);
        $this->multilingual(Post::class);
        $this->multilingual(PostMeta::class);
        $this->multilingual(Tag::class);
    }

    protected function multilingual($modelClass)
    {
        $this->factory->define($modelClass, function () {
            return ['language_code' => 'da'];
        });

        $this->factory->state($modelClass, 'english', function () {
            return ['language_code' => 'en'];
        });

        $this->factory->state($modelClass, 'swedish', function () {
            return ['language_code' => 'sv'];
        });
    }
};
