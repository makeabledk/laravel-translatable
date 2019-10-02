<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class BelongsToManyTest extends TestCase
{
    /** @test **/
    public function it_can_eager_load_translated_belongs_to_many_relationships()
    {
        $masterPost = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create()
            ->images()->attach($image = factory(Image::class)->create());

        $image = Image::whereKey($image->id)->with(['posts' => function ($query) {
            $query->language('sv');
        }])->first();

        $this->assertEquals(1, $image->posts->count());
        $this->assertEquals('sv', $image->posts->first()->language_code);
    }

    /** @test * */
    public function it_defaults_to_fetch_the_best_matching_language_to_the_parent()
    {
        $masterPost = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'categories')
            ->with(1, 'english', 'categories.translations')
            ->times(2)
            ->create()
            ->last();

        $this->assertEquals(1, $masterPost->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('da', $masterPost->categories()->first()->language_code, 'Master version will be loaded unless a specific language is requested');

        $this->assertEquals(1, $masterPost->getTranslation('en')->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('en', $masterPost->getTranslation('en')->categories()->first()->language_code, 'It fetches the same language as the parent when available');

        $this->assertEquals(1, $masterPost->getTranslation('sv')->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('da', $masterPost->getTranslation('sv')->categories()->first()->language_code, 'It defaults to master when parent language is not available');
    }

    /** @test **/
    public function it_can_get_non_translatable_belongs_to_many_relations_from_translatable_model()
    {
        $translation = factory(Post::class)
            ->state('english')
            ->with('master')
            ->with(1, 'master.images', ['src' => 'Foo'])
            ->create();

        $this->assertEquals(1, $translation->images->count());
        $this->assertEquals('en', $translation->language_code);
        $this->assertEquals('Foo', $translation->images->first()->src);
    }

    /** @test **/
    public function it_can_eager_load_nested_translated_belongs_to_many_models()
    {
        $image = factory(Image::class)
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->create();

        // TODO test eager-loaded nested relations

        $result = $image->load([
            'posts' => function ($posts) {
                $posts->language('en');
            },
            'posts.images',
            'posts.images.posts' => function ($posts) {
                $posts->language('en');
            },
        ])->toArray();

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.images')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.images.0.posts')));
        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.images.0.posts.0.language_code'));
    }
}
