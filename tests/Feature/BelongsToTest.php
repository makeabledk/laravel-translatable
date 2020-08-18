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
        $this->assertEquals($postTranslation->id, $metaTranslation->post->id, 'When no locale set, it defaults to current locale of child');
        $this->assertEquals('en', $metaTranslation->post->locale, 'When no locale set, it defaults to current locale of child');

        $this->assertEquals(1, $metaTranslation->post()->count(), 'It always just matches 1 parent at a time');
        $this->assertEquals(1, $metaTranslation->post()->locale('da')->count());
        $this->assertEquals($postMaster->id, $metaTranslation->post()->locale('da')->first()->id, 'Another locale may be set on the relation which changes the outcome');
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

        $load = function ($locale) {
            return Post::latest()
                ->locale($locale)
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
        $this->assertEquals('da', $translation->post()->withoutLocaleScope()->first()->locale);
        $this->assertEquals(1, $translation->post()->withoutLocaleScope()->take(5)->count());

        // Eager load
        $this->assertEquals('en', $translation->load('post')->post->locale);
        $this->assertEquals('da', $translation->load(['post' => function ($query) {
            $query->withoutLocaleScope();
        }])->post->locale);
    }

    /** @test **/
    public function regression_it_loads_the_default_locale_for_belongs_to_even_on_compatibility_mode()
    {
        $comment = factory(Comment::class)
            ->with(1, 'post')
            ->with('english', 'post.translations')
            ->create();

        Translatable::fetchAllLocalesByDefault();

        $this->assertEquals('da', $comment->post()->latest('id')->first()->locale);
        $this->assertEquals('da', $comment->load(['post' => function ($q) {
            $q->orderBy('id');
        }])->post->locale); // first result for eager-loads since no limit on this query

        Translatable::fetchMasterLocaleByDefault(); // reset
    }

    /** @test **/
    public function regression_it_always_fetches_the_exact_child_id_in_compatibility_mode()
    {
        factory(PostMeta::class)
            ->with(1, 'english', 'translations')
            ->with(1, 'english', 'post.translations')
            ->create();

        Translatable::fetchAllLocalesByDefault();

        // This issue occurs when eager loading parents onto their children, and parents are fetched in multiple languages.
        // When the bug exists, it would then fetch all parents in whatever language the first row was. Instead, the
        // correct behavior would be to only fetch the master version, which is what would normally happen without
        // this package installed.
        $meta = PostMeta::latest('id') // Trigger the issue by loading the english version first.
            ->get()
            ->load('post');

        $this->assertEquals('en', $meta->first()->locale);
        $this->assertEquals('da', $meta->first()->post->locale);
        $this->assertEquals('da', $meta->last()->locale);
        $this->assertEquals('da', $meta->last()->post->locale);

        Translatable::fetchMasterLocaleByDefault(); // reset
    }
}
