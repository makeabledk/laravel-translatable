<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class TranslatableTest extends TestCase
{
    /** @test **/
    public function the_siblings_relation_returns_all_versions_but_the_current_instance()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['en', 'sv'], $master->siblings->pluck('locale')->toArray());
        $this->assertEquals(['da', 'sv'], $master->getTranslation('en')->siblings->pluck('locale')->toArray());

        // Test eager load
        $master->setRelations([])->load('siblings');

        $this->assertEquals(['en', 'sv'], $master->siblings->pluck('locale')->toArray());
    }

    /** @test * */
    public function the_translations_relation_returns_all_versions_but_master()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['en', 'sv'], $master->translations->pluck('locale')->toArray());
        $this->assertEquals(['en', 'sv'], $master->getTranslation('en')->translations->pluck('locale')->toArray());
    }

    /** @test * */
    public function the_versions_relation_returns_all_translations_including_master()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $this->assertEquals(['da', 'en', 'sv'], $master->versions->pluck('locale')->toArray());
        $this->assertEquals(['da', 'en', 'sv'], $master->getTranslation('en')->versions->pluck('locale')->toArray());
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

    /** @test **/
    public function it_sets_the_sibling_id_attribute_on_saving()
    {
        // When master: master_id = NULL, sibling_id = id
        $master = factory(Post::class)->create();
        $this->assertNull($master->master_id);
        $this->assertEquals($master->id, $master->sibling_id);

        // When translation: master_id = master.id, sibling_id = master.id
        $translation = factory(Post::class)->create(['master_id' => $master->id]);
        $this->assertEquals($master->id, $translation->sibling_id);

        // Check updating
        $translation->update(['master_id' => null]);
        $this->assertEquals($translation->id, $translation->sibling_id);
    }
}
