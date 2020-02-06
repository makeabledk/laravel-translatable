<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\TestCase;

class MorphManyTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_morph_many_relationships_from_translated_model()
    {
        $masterPost = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

        $masterTag = factory(Tag::class)
            ->with(1, 'english', 'translations')
            ->create();

        $translatedTag = $masterTag->getTranslation('en');
        $translatedPost = $masterPost->getTranslation('en');
        $translatedPost->tags()->save($translatedTag);

        // Check correct attachment
        $this->assertEquals($masterPost->getMorphClass(), $masterTag->refresh()->taggable_type);
        $this->assertEquals($masterPost->id, $masterTag->taggable_id);
        $this->assertEquals($masterPost->getMorphClass(), $translatedTag->taggable_type);
        $this->assertEquals($masterPost->id, $translatedTag->taggable_id);

        // Normal query
        $this->assertEquals(1, $translatedPost->tags()->count());
        $this->assertEquals($masterTag->id, $masterPost->tags->first()->id ?? null);
        $this->assertEquals($translatedTag->id, $translatedPost->tags->first()->id ?? null);

        // Eager load
        $masterPost->setRelations([])->load('tags');
        $this->assertEquals($masterTag->id, $masterPost->tags->first()->id ?? null);

        $translatedPost->setRelations([])->load('tags');
        $this->assertEquals($translatedTag->id, $translatedPost->tags->first()->id ?? null);
    }
}
