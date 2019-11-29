<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class TranslatableTest extends TestCase
{
    /** @test * */
    public function the_siblings_relation_returns_all_versions_but_the_current_instance()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['en', 'sv'], $master->siblings->pluck('language_code')->toArray());
        $this->assertEquals(['da', 'sv'], $master->getTranslation('en')->siblings->pluck('language_code')->toArray());
    }

    /** @test * */
    public function the_translations_relation_returns_all_versions_but_master()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['en', 'sv'], $master->translations->pluck('language_code')->toArray());
        $this->assertEquals(['en', 'sv'], $master->getTranslation('en')->translations->pluck('language_code')->toArray());
    }

    /** @test * */
    public function the_versions_relation_returns_all_translations_including_master()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['da', 'en', 'sv'], $master->versions->pluck('language_code')->toArray());
        $this->assertEquals(['da', 'en', 'sv'], $master->getTranslation('en')->versions->pluck('language_code')->toArray());
    }

    /** @test **/
    public function the_master_relation_returns_the_master_version()
    {
        $english = factory(Post::class)
            ->state('english')
            ->with('master')
            ->create();

        $this->assertEquals('da', $english->refresh()->master->language_code);
    }
}
