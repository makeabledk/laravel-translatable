<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class DefaultLanguageTest extends TestCase
{
    /** @test **/
    public function it_defaults_to_only_fetch_master()
    {
        factory(Post::class)->with(1, 'english', 'translations')->create();

        $this->assertEquals(1, Post::all()->count());
    }

    /** @test **/
    public function it_does_not_apply_default_scope_when_refreshing()
    {
        $master = factory(Post::class)->with(1, 'english', 'translations')->create();
        $translation = $master->getTranslation('en');

        $this->assertEquals($translation->id, $translation->refresh()->id);
    }
}
