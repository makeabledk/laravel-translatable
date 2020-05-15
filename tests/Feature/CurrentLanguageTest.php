<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\TestCase;

class CurrentLanguageTest extends TestCase
{
    /** @test **/
    public function when_a_local_locale_is_set_on_a_model_it_always_fetches_that_locale()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        Post::setLocale('en');

        $this->assertEquals('en', Post::getCurrentLocale());
        $this->assertEquals(1, ($posts = Post::all())->count());
        $this->assertEquals('en', $posts->first()->locale);
    }

    /** @test **/
    public function when_a_global_locale_is_set_it_always_fetches_that_locale_across_models()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        Tag::setGlobalLocale('en'); // Can be any translatable model

        $this->assertEquals('en', Post::getCurrentLocale());
        $this->assertEquals(1, ($posts = Post::all())->count());
        $this->assertEquals('en', $posts->first()->locale);
    }

    /** @test **/
    public function regression_it_does_not_apply_current_locale_when_disabled_on_relation()
    {
        $post = factory(Post::class)
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        PostMeta::setGlobalLocale('en');

        $this->assertEquals('en', $post->meta()->first()->locale);
        $this->assertEquals('da', $post->meta()->withoutLocaleScope()->first()->locale);
    }

    /** @test **/
    public function it_respects_default_master_policy_when_loading_relations()
    {
        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->with(1, 'meta')
            ->create();

        Post::setGlobalLocale('en');

        $post = Post::with('meta')->first();

        $this->assertEquals('en', $post->locale);
        $this->assertEquals(0, $post->meta->count());
    }
}
