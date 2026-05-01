<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Facades\Translations;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\TestCase;
use Makeable\LaravelTranslatable\Translatable;
use RuntimeException;

class TranslationManagerTest extends TestCase
{
    public function test_the_translations_facade_manages_the_global_locale()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        Translations::setGlobalLocale('en');

        $this->assertEquals('en', Post::getCurrentLocale());
        $this->assertEquals('en', Tag::getCurrentLocale());
        $this->assertEquals('en', Post::first()->locale);
    }

    public function test_legacy_trait_static_calls_share_state_with_the_translations_facade()
    {
        Post::setGlobalLocale('en');

        $this->assertEquals('en', Translations::getCurrentLocale());

        Translations::setGlobalLocale('sv');

        $this->assertEquals('sv', Post::getCurrentLocale());
    }

    public function test_legacy_model_static_calls_share_state_with_the_translations_facade()
    {
        Post::setLocale('en');

        $this->assertEquals('en', Translations::getCurrentLocale(Post::class));
        $this->assertNull(Translations::getCurrentLocale(Tag::class));
    }

    public function test_global_locale_may_be_applied_for_a_closure()
    {
        Translations::setGlobalLocale('en', function () {
            $this->assertEquals('en', Post::getCurrentLocale());
        });

        $this->assertNull(Post::getCurrentLocale());
    }

    public function test_global_locale_is_reset_when_a_closure_throws()
    {
        try {
            Translations::setGlobalLocale('en', function () {
                throw new RuntimeException('Stop');
            });
        } catch (RuntimeException $exception) {
            $this->assertEquals('Stop', $exception->getMessage());
        }

        $this->assertNull(Post::getCurrentLocale());
    }
}
