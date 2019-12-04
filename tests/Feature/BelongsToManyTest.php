<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Category;
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
        factory(Category::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->times(2)
            ->create();

        $load = function ($language) {
            return Category::latest()
                ->language($language)
                ->with('posts.categories.posts')
                ->first()
                ->toArray();
        };

        // The language should be inherited all the way down
        $result = $load('en');

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories.0.posts')));
        $this->assertEquals('en', data_get($result, 'language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.categories.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.categories.0.posts.0.language_code'));

        // When a child doesn't exist in the requested language, it will default to master.
        // When nesting further relation, it will keep trying the originally requested
        // language, and only use master on the individual models where necessary.
        $result = $load('sv');

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories.0.posts')));
        $this->assertEquals('sv', data_get($result, 'language_code'));
        $this->assertEquals('da', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('sv', data_get($result, 'posts.0.categories.0.language_code'));
        $this->assertEquals('da', data_get($result, 'posts.0.categories.0.posts.0.language_code'));
    }

    /** @test * */
    public function belongs_to_many_language_scope_may_be_disabled()
    {
        $translation = factory(Post::class)
            ->state('english')
            ->with(2, 'master.categories')
            ->create();

        // Relation
        $this->assertEquals(2, $translation->categories()->count());
        $this->assertEquals(0, $translation->categories()->withoutLanguageScope()->count());

        // Eager load
        $this->assertEquals(2, $translation->load('categories')->categories->count());
        $this->assertEquals(0, $translation->load(['categories' => function ($query) {
            $query->withoutLanguageScope();

            // Let's also check if the query actually returns any results, but just didn't
            // match on model hydration!
            $this->assertEquals(0, $query->count());
        }])->categories->count());
    }

    /** @test **/
    public function regression_it_works_with_simple_pagination_on_belongs_to_many()
    {
        $post = factory(Post::class)
            ->state('english')
            ->with(2, 'master.categories')->create();

        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(2, count($post->categories()->simplePaginate()));
    }

//    TODO implement BelongsToMany existence query
//
//    /** @test **/
//    public function it_can_query_relation_existence_on_translated_has_many_relations()
//    {
//        $translation = factory(Post::class)
//            ->state('english')
//            ->with('master')
//            ->with(1, 'meta')
//            ->with(1, 'english', 'meta.translations')
//            ->create();
//
//        $this->assertEquals(1, Post::whereKey($translation->id)->has('meta', '>=', 2)->get()->count());
//        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('meta', $this->ofLanguage('en'))->get()->count());
//        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('meta', $this->ofLanguage('sv'))->get()->count());
//
//        // On self (separate implementation)
//        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('translations', $this->ofLanguage('en'))->get()->count());
//        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('translations', $this->ofLanguage('sv'))->get()->count());
//    }
//
//    /** @test * */
//    public function regression_when_disabling_language_scope_it_also_applies_to_with_count_method()
//    {
//        $englishPost = factory(Post::class)
//            ->state('english')
//            ->with(2, 'master.meta')
//            ->create();
//
//        $this->assertEquals(2, Post::whereKey($englishPost->id)->withCount('meta')->first()->meta_count);
//        $this->assertEquals(2, Post::whereKey($englishPost->id)->withCount('directMeta')->first()->direct_meta_count);
//    }
}
