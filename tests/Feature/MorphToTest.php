<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\TestCase;

class MorphToTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_morph_to_relationships_from_translated_child()
    {
        $postMaster = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->times(2)
            ->create()
            ->last(); // Ensure the post() relation on Meta doesn't just select the first post in table, but actually matches foreign key constraints

        $tagMaster = factory(Tag::class)->create();
        $tagTranslation = $tagMaster->getTranslationOrNew('en');
        $tagTranslation->taggable()->associate($postTranslation = $postMaster->getTranslation('en'))->save();

        $this->assertEquals($postMaster->getMorphClass(), $tagTranslation->taggable_type);
        $this->assertEquals($postMaster->getMorphClass(), $tagMaster->refresh()->taggable_type);

        $this->assertEquals($postMaster->id, $tagTranslation->taggable_id, 'It sets the master id');
        $this->assertEquals($postTranslation->id, $tagTranslation->taggable->id, 'When no language set, it defaults to current language of child');
        $this->assertEquals('en', $tagTranslation->taggable->language_code, 'When no language set, it defaults to current language of child');

        $this->assertEquals(1, $tagTranslation->taggable()->count(), 'It always just matches 1 parent at a time');
        $this->assertEquals(1, $tagTranslation->taggable()->language('da')->count());
        $this->assertEquals($postMaster->id, $tagTranslation->taggable()->language('da')->first()->id, 'Another language may be set on the relation which changes the outcome');
    }

    /** @test **/
    public function it_can_load_nested_translatable_morph_to_relations()
    {
        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'tags')
            ->with(1, 'english', 'tags.translations')
            ->times(2)
            ->create();

        $load = function ($language) {
            return Post::latest()
                ->language($language)
                ->with('tags.post')
                ->first()
                ->toArray();
        };

        $result = $load('en');
        $this->assertEquals(1, count(data_get($result, 'tags')));
        $this->assertEquals('en', data_get($result, 'language_code'));
        $this->assertEquals('en', data_get($result, 'tags.0.language_code'));
        $this->assertEquals('en', data_get($result, 'tags.0.post.language_code'));

        $result = $load('sv');
        $this->assertEquals(1, count(data_get($result, 'tags')));
        $this->assertEquals('sv', data_get($result, 'language_code'));
        $this->assertEquals('da', data_get($result, 'tags.0.language_code'), 'Fallback to master (da)');
        $this->assertEquals('sv', data_get($result, 'tags.0.post.language_code'));
    }


//    /** @test **/
//    public function it_eager_loads_translated_morph_to()
//    {
//        $postMaster = factory(Post::class)
//            ->with(1, 'tags')
//            ->with(1, 'english', 'translations')
//            ->times(2)
//            ->create()
//            ->last();
//
//        $tagMaster = factory(Tag::class)->create();
//        $tagTranslation = $tagMaster->getTranslationOrNew('en');
//        $tagTranslation->taggable()->associate($postTranslation = $postMaster->getTranslation('en'))->save();
//
//        $this->assertEquals('da', $tagMaster->setRelations([])->refresh()->load('taggable')->taggable->language_code);
//        $this->assertEquals('en', $tagTranslation->setRelations([])->load('taggable')->taggable->language_code);
//    }
}
