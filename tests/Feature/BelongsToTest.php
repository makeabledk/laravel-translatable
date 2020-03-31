<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Domain\Core\ContentType\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Comment;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\TestCase;
use Makeable\LaravelTranslatable\Translatable;

class BelongsToTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_belongs_to_relationships_from_translated_child()
    {
        $postMaster = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->times(2)
            ->create()
            ->last(); // Ensure the post() relation on Meta doesn't just select the first post in table, but actually matches foreign key constraints

        $metaMaster = factory(PostMeta::class)->create();
        $metaTranslation = $metaMaster->getTranslationOrNew('en');

        $metaTranslation->post()->associate($postTranslation = $postMaster->getTranslation('en'))->save();

        $this->assertEquals($postMaster->id, $metaTranslation->post_id, 'It sets the master id');
        $this->assertEquals($postTranslation->id, $metaTranslation->post->id, 'When no language set, it defaults to current language of child');
        $this->assertEquals('en', $metaTranslation->post->language_code, 'When no language set, it defaults to current language of child');

        $this->assertEquals(1, $metaTranslation->post()->count(), 'It always just matches 1 parent at a time');
        $this->assertEquals(1, $metaTranslation->post()->language('da')->count());
        $this->assertEquals($postMaster->id, $metaTranslation->post()->language('da')->first()->id, 'Another language may be set on the relation which changes the outcome');
    }

    /** @test **/
    public function it_can_load_nested_translatable_belongs_to_relations()
    {
        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->times(2)
            ->create();

        $load = function ($language) {
            return Post::latest()
                ->language($language)
                ->with('meta.post')
                ->first()
                ->toArray();
        };

        $result = $load('en');
        $this->assertEquals(1, count(data_get($result, 'meta')));
        $this->assertEquals('en', data_get($result, 'language_code'));
        $this->assertEquals('en', data_get($result, 'meta.0.language_code'));
        $this->assertEquals('en', data_get($result, 'meta.0.post.language_code'));

        $result = $load('sv');
        $this->assertEquals(1, count(data_get($result, 'meta')));
        $this->assertEquals('sv', data_get($result, 'language_code'));
        $this->assertEquals('da', data_get($result, 'meta.0.language_code'), 'Fallback to master (da)');
        $this->assertEquals('sv', data_get($result, 'meta.0.post.language_code'));
    }

    /** @test **/
    public function belongs_to_language_scope_may_be_disabled()
    {
        $translation = factory(PostMeta::class)
            ->state('english')
            ->with('master.post')
            ->with('english', 'master.post.translations')
            ->create();

        // Relation
        $this->assertEquals('en', $translation->post()->first()->language_code);
        $this->assertEquals(1, $translation->post()->take(5)->count());
        $this->assertEquals('da', $translation->post()->withoutLanguageScope()->first()->language_code);
        $this->assertEquals(1, $translation->post()->withoutLanguageScope()->take(5)->count());

        // Eager load
        $this->assertEquals('en', $translation->load('post')->post->language_code);
        $this->assertEquals('da', $translation->load(['post' => function ($query) {
            $query->withoutLanguageScope();
        }])->post->language_code);
    }

    /** @test **/
    public function regression_it_loads_the_default_language_for_belongs_to_even_on_compatibility_mode()
    {
        $comment = factory(Comment::class)
            ->with(1, 'post')
            ->with('english', 'post.translations')
            ->create();

        Translatable::fetchAllLanguagesByDefault();

        $this->assertEquals('da', $comment->post()->latest('id')->first()->language_code);
        $this->assertEquals('da', $comment->load(['post' => function ($q) { $q->orderBy('id'); }])->post->language_code); // first result for eager-loads since no limit on this query

        Translatable::fetchMasterLanguageByDefault(); // reset
    }
}
