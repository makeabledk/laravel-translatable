<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\TestCase;

class CurrentLanguageTest extends TestCase
{
    /** @test **/
    public function when_a_local_language_is_set_on_a_model_it_always_fetches_that_language()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        Post::setLanguage('en');

        $this->assertEquals('en', Post::getCurrentLanguage());
        $this->assertEquals(1, ($posts = Post::all())->count());
        $this->assertEquals('en', $posts->first()->language_code);

        Post::setLanguage(null); // reset
    }

    /** @test **/
    public function when_a_global_language_is_set_it_always_fetches_that_language_across_models()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        Tag::setGlobalLanguage('en'); // Can be any translatable model

        $this->assertEquals('en', Post::getCurrentLanguage());
        $this->assertEquals(1, ($posts = Post::all())->count());
        $this->assertEquals('en', $posts->first()->language_code);

        Tag::setGlobalLanguage(null);
    }

    /** @test **/
    public function regression_it_does_not_apply_current_language_when_disabled_on_relation()
    {
        $post = factory(Post::class)
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        PostMeta::setGlobalLanguage('en');

        $this->assertEquals('en', $post->meta()->first()->language_code);
        $this->assertEquals('da', $post->meta()->withoutLanguageScope()->first()->language_code);
    }
}
