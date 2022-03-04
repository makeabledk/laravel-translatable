<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Category;
use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\Team;
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
            $query->locale('sv');
        }])->first();

        $this->assertEquals(1, $image->posts->count());
        $this->assertEquals('sv', $image->posts->first()->locale);
    }

    /** @test * */
    public function it_defaults_to_fetch_the_best_matching_locale_to_the_parent()
    {
        $masterPost = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'categories')
            ->with(1, 'english', 'categories.translations')
            ->count(2)
            ->create()
            ->last();

        $this->assertEquals(1, $masterPost->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('da', $masterPost->categories()->first()->locale, 'Master version will be loaded unless a specific locale is requested');

        $this->assertEquals(1, $masterPost->getTranslation('en')->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('en', $masterPost->getTranslation('en')->categories()->first()->locale, 'It fetches the same locale as the parent when available');

        $this->assertEquals(1, $masterPost->getTranslation('sv')->categories()->count(), 'It only matches a single translation');
        $this->assertEquals('da', $masterPost->getTranslation('sv')->categories()->first()->locale, 'It defaults to master when parent locale is not available');
    }

    /** @test **/
    public function it_can_get_non_translatable_belongs_to_many_relations_from_translatable_model()
    {
        $translation = factory(Post::class)
            ->apply('english')
            ->with(1, 'master.images', ['src' => 'Foo'])
            ->create();

        $this->assertEquals(1, $translation->images->count());
        $this->assertEquals('en', $translation->locale);
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
            ->count(2)
            ->create();

        $load = function ($locale) {
            return Category::latest()
                ->locale($locale)
                ->with('posts.categories.posts')
                ->first()
                ->toArray();
        };

        // The locale should be inherited all the way down
        $result = $load('en');

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories.0.posts')));
        $this->assertEquals('en', data_get($result, 'locale'));
        $this->assertEquals('en', data_get($result, 'posts.0.locale'));
        $this->assertEquals('en', data_get($result, 'posts.0.categories.0.locale'));
        $this->assertEquals('en', data_get($result, 'posts.0.categories.0.posts.0.locale'));

        // When a child doesn't exist in the requested locale, it will default to master.
        // When nesting further relation, it will keep trying the originally requested
        // locale, and only use master on the individual models where necessary.
        $result = $load('sv');

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.categories.0.posts')));
        $this->assertEquals('sv', data_get($result, 'locale'));
        $this->assertEquals('da', data_get($result, 'posts.0.locale'));
        $this->assertEquals('sv', data_get($result, 'posts.0.categories.0.locale'));
        $this->assertEquals('da', data_get($result, 'posts.0.categories.0.posts.0.locale'));
    }

    /** @test * */
    public function belongs_to_many_locale_scope_may_be_disabled()
    {
        $translation = factory(Post::class)
            ->apply('english')
            ->with(2, 'master.categories')
            ->create();

        // Relation
        $this->assertEquals(2, $translation->categories()->count());
        $this->assertEquals(0, $translation->categories()->withoutLocaleScope()->count());

        // Eager load
        $this->assertEquals(2, $translation->load('categories')->categories->count());
        $this->assertEquals(0, $translation->load(['categories' => function ($query) {
            $query->withoutLocaleScope();

            // Let's also check if the query actually returns any results, but just didn't
            // match on model hydration!
            $this->assertEquals(0, $query->count());
        }])->categories->count());
    }

    /** @test **/
    public function regression_it_works_with_simple_pagination_on_belongs_to_many()
    {
        $post = factory(Post::class)
            ->apply('english')
            ->with(2, 'master.categories')->create();

        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(2, count($post->categories()->simplePaginate()));
    }

    /** @test * */
    public function regression_belongs_to_many_works_between_non_translatable_models()
    {
        $team = factory(Team::class)->with(1, 'servers')->create();

        $this->assertEquals(1, $team->servers()->get()->count());
    }

//    TODO implement BelongsToMany existence query
//
//    /** @test **/
//    public function it_can_query_relation_existence_on_translated_has_many_relations()
//    {
//        $translation = factory(Post::class)
//            ->apply('english')
//            ->with('master')
//            ->with(1, 'meta')
//            ->with(1, 'english', 'meta.translations')
//            ->create();
//
//        $this->assertEquals(1, Post::whereKey($translation->id)->has('meta', '>=', 2)->get()->count());
//        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('meta', $this->ofLocale('en'))->get()->count());
//        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('meta', $this->ofLocale('sv'))->get()->count());
//
//        // On self (separate implementation)
//        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('translations', $this->ofLocale('en'))->get()->count());
//        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('translations', $this->ofLocale('sv'))->get()->count());
//    }
//
//    /** @test * */
//    public function regression_when_disabling_locale_scope_it_also_applies_to_with_count_method()
//    {
//        $englishPost = factory(Post::class)
//            ->apply('english')
//            ->with(2, 'master.meta')
//            ->create();
//
//        $this->assertEquals(2, Post::whereKey($englishPost->id)->withCount('meta')->first()->meta_count);
//        $this->assertEquals(2, Post::whereKey($englishPost->id)->withCount('directMeta')->first()->direct_meta_count);
//    }
}
