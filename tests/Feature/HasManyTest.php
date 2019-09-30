<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Team;
use Makeable\LaravelTranslatable\Tests\TestCase;

class HasManyTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_has_many_relationships_from_translated_model()
    {
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

        $translation = $master->getTranslation('en');
        $translation->meta()->save($meta = new PostMeta);

        $this->assertEquals($master->id, $meta->post_id);
        $this->assertEquals(1, $translation->meta()->count());
        $this->assertEquals($meta->id, $translation->meta->first()->id ?? null);
        $this->assertEquals($meta->id, $master->meta->first()->id ?? null);
    }

    /** @test **/
    public function it_can_eager_load_has_many_from_translated_model()
    {
        $translation = factory(Post::class)
            ->state('english')
            ->with(2, 'master.meta', ['key' => 'foo'])
            ->create()
            ->load('meta');

        $this->assertEquals(2, $translation->meta->count());
        $this->assertEquals('en', $translation->language_code);
        $this->assertEquals('foo', $translation->meta->first()->key);
    }

    /** @test **/
    public function the_has_many_translatable_models_includes_both_master_and_translations()
    {
        $master = factory(Post::class)
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        $this->assertEquals(2, $master->loadCount('meta')->meta_count);
        $this->assertEquals(2, $master->meta()->count());
        $this->assertEquals(2, $master->meta->count());
        $this->assertEquals('da', $master->meta->get(0)->language_code);
        $this->assertEquals('en', $master->meta->get(1)->language_code);
    }

    /** @test **/
    public function a_non_translatable_model_can_have_many_translatable_relations()
    {
        $team = factory(Team::class)
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->create();

        $this->assertEquals(2, $team->posts->count());
    }

    /** @test **/
    public function it_can_load_nested_translatable_has_many_relations()
    {
        $team = factory(Team::class)
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->with(1, 'posts.meta')
            ->with(1, 'english', 'posts.meta.translations')
            ->create();

        $result = $team->load([
            'posts' => function ($posts) {
                $posts->language('en');
            },
            'posts.meta' => function ($posts) {
                $posts->language('en');
            },
        ])->toArray();

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.meta')));
        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.meta.0.language_code'));
    }
}
