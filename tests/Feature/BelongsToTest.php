<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

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
        $this->assertEquals('en', $metaTranslation->post->locale, 'When no language set, it defaults to current language of child');

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
        $this->assertEquals('en', data_get($result, 'locale'));
        $this->assertEquals('en', data_get($result, 'meta.0.locale'));
        $this->assertEquals('en', data_get($result, 'meta.0.post.locale'));

        $result = $load('sv');
        $this->assertEquals(1, count(data_get($result, 'meta')));
        $this->assertEquals('sv', data_get($result, 'locale'));
        $this->assertEquals('da', data_get($result, 'meta.0.locale'), 'Fallback to master (da)');
        $this->assertEquals('sv', data_get($result, 'meta.0.post.locale'));
    }

    /** @test **/
    public function belongs_to_locale_scope_may_be_disabled()
    {
        $translation = factory(PostMeta::class)
            ->state('english')
            ->with('master.post')
            ->with('english', 'master.post.translations')
            ->create();

        // Relation
        $this->assertEquals('en', $translation->post()->first()->locale);
        $this->assertEquals(1, $translation->post()->take(5)->count());
        $this->assertEquals('da', $translation->post()->withoutLanguageScope()->first()->locale);
        $this->assertEquals(1, $translation->post()->withoutLanguageScope()->take(5)->count());

        // Eager load
        $this->assertEquals('en', $translation->load('post')->post->locale);
        $this->assertEquals('da', $translation->load(['post' => function ($query) {
            $query->withoutLanguageScope();
        }])->post->locale);
    }

    /** @test **/
    public function regression_it_loads_the_default_language_for_belongs_to_even_on_compatibility_mode()
    {
        $comment = factory(Comment::class)
            ->with(1, 'post')
            ->with('english', 'post.translations')
            ->create();

        Translatable::fetchAllLanguagesByDefault();

        $this->assertEquals('da', $comment->post()->latest('id')->first()->locale);
        $this->assertEquals('da', $comment->load(['post' => function ($q) {
            $q->orderBy('id');
        }])->post->locale); // first result for eager-loads since no limit on this query

        Translatable::fetchMasterLanguageByDefault(); // reset
    }
}
