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
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertNull($master->master);
        $this->assertEquals($master->id, $master->getTranslation('en')->master->id);
        $this->assertEquals($master->id, $master->getTranslation('sv')->master->id);

        // Regression - ensure that there is only 1 match
        $this->assertEquals(1, $master->getTranslation('en')->master()->take(5)->count());
    }
}
