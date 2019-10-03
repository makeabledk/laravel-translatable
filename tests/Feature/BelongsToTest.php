<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Category;
use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Team;
use Makeable\LaravelTranslatable\Tests\TestCase;

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

        $metaMaster = PostMeta::create();
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

//        dd(
//            Post::all()->toArray()
////                ->language('en')
////                ->with('meta.post')
////                ->first()
//        );


        $load = function($language) {
            return Post::latest()
                ->language($language)
                ->with('meta.post')
                ->first()
                ->toArray();
        };

//        \DB::listen(function ($e) {
//            dump($e->sql);
//        });

        $result = $load('en');

//        dd($result);


        $this->assertEquals(1, count(data_get($result, 'meta')));
        $this->assertEquals('en', data_get($result, 'language_code'));
        $this->assertEquals('en', data_get($result, 'meta.0.language_code'));
        $this->assertEquals('en', data_get($result, 'meta.0.post.language_code'));
    }


    // TODO eager-loaded nested relations


//
//    /** @test **/
//    public function it_can_eager_load_has_many_from_translated_model()
//    {
//        $translation = factory(Post::class)
//            ->state('english')
//            ->with(2, 'master.meta', ['key' => 'foo'])
//            ->create()
//            ->load('meta');
//
//        $this->assertEquals(2, $translation->meta->count());
//        $this->assertEquals('en', $translation->language_code);
//        $this->assertEquals('foo', $translation->meta->first()->key);
//    }
//
//    /** @test **/
//    public function the_has_many_translatable_models_includes_both_master_and_translations()
//    {
//        $master = factory(Post::class)
//            ->with(1, 'meta')
//            ->with(1, 'english', 'meta.translations')
//            ->create();
//
//        $this->assertEquals(2, $master->loadCount('meta')->meta_count);
//        $this->assertEquals(2, $master->meta()->count());
//        $this->assertEquals(2, $master->meta->count());
//        $this->assertEquals('da', $master->meta->get(0)->language_code);
//        $this->assertEquals('en', $master->meta->get(1)->language_code);
//    }
//
//    /** @test **/
//    public function a_non_translatable_model_can_have_many_translatable_relations()
//    {
//        $team = factory(Team::class)
//            ->with(1, 'posts')
//            ->with(1, 'english', 'posts.translations')
//            ->create();
//
//        $this->assertEquals(2, $team->posts->count());
//    }
//
//    /** @test **/
//    public function it_can_load_nested_translatable_has_many_relations()
//    {
//        $team = factory(Team::class)
//            ->with(1, 'posts')
//            ->with(1, 'english', 'posts.translations')
//            ->with(1, 'posts.meta')
//            ->with(1, 'english', 'posts.meta.translations')
//            ->create();
//
//        $result = $team->load([
//            'posts' => function ($posts) {
//                $posts->language('en');
//            },
//            'posts.meta' => function ($posts) {
//                $posts->language('en');
//            },
//        ])->toArray();
//
//        $this->assertEquals(1, count(data_get($result, 'posts')));
//        $this->assertEquals(1, count(data_get($result, 'posts.0.meta')));
//        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
//        $this->assertEquals('en', data_get($result, 'posts.0.meta.0.language_code'));
//    }
}
