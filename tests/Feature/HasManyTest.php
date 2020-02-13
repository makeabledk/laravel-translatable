<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Category;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\PostMeta;
use Makeable\LaravelTranslatable\Tests\Stubs\Team;
use Makeable\LaravelTranslatable\Tests\TestCase;

class HasManyTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_has_many_relationships_from_translated_model()
    {
//        factory(Post::class)->create();
//
//            dd(Post::language('da')->get());
        ////        get_class(
        ////        );
        ////        dd(
        ////        );

        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

//        define('OK_TEST', true);

//        \DB::listen(function ($e) {
//            dump($e->sql, $e->bindings);
//        });

        $translation = $master->getTranslation('en');
        $translation->meta()->save($meta = factory(PostMeta::class)->make());

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
    public function the_has_many_translatable_models_always_selects_best_matching_language()
    {
        $postMaster = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->times(2)
            ->create()
            ->first();

        $this->assertEquals(1, $postMaster->meta->count());
        $this->assertEquals('da', $postMaster->meta->first()->language_code);

        $this->assertEquals(1, ($english = $postMaster->getTranslation('en'))->meta->count());
        $this->assertEquals('en', $english->meta->first()->language_code);

        $this->assertEquals(1, ($swedish = $postMaster->getTranslation('sv'))->meta->count(), 'It should default to master when language not available');
        $this->assertEquals('da', $swedish->meta->first()->language_code, 'It should default to master when language not available');
    }

    /** @test **/
    public function it_defaults_to_master_language_when_parent_is_non_translatable()
    {
        $team = factory(Team::class)
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->create();

        $this->assertEquals(1, $team->posts->count(), 'Master version will be loaded unless a specific language is requested');
        $this->assertEquals('da', $team->posts->first()->language_code, 'Master version will be loaded unless a specific language is requested');

        $this->assertEquals(1, $team->posts()->language('en')->count(), 'A specific language is requested');
        $this->assertEquals('en', $team->posts()->language('en')->first()->language_code, 'A specific language is requested');
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
            'posts.meta',
        ])->toArray();

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.meta')));
        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.meta.0.language_code'));
    }

    /** @test **/
    public function it_can_query_relation_existence_on_translated_has_many_relations()
    {
        $translation = factory(Post::class)
            ->state('english')
            ->with('master')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        $this->assertEquals(1, Post::whereKey($translation->id)->has('meta', '>=', 2)->get()->count());
        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('meta', $this->ofLanguage('en'))->get()->count());
        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('meta', $this->ofLanguage('sv'))->get()->count());

        // On self (separate implementation)
        $this->assertEquals(1, Post::whereKey($translation->id)->whereHas('translations', $this->ofLanguage('en'))->get()->count());
        $this->assertEquals(0, Post::whereKey($translation->id)->whereHas('translations', $this->ofLanguage('sv'))->get()->count());
    }

    /** @test * */
    public function regression_when_disabling_language_scope_it_also_applies_to_with_count_method()
    {
        $englishPost = factory(Post::class)
            ->state('english')
            ->with(2, 'master.meta')
            ->create();

        $this->assertEquals(2, Post::whereKey($englishPost->id)->withCount('meta')->first()->meta_count);
        $this->assertEquals(0, Post::whereKey($englishPost->id)->withCount('directMeta')->first()->direct_meta_count);
    }

    /** @test * */
    public function has_many_language_scope_may_be_disabled()
    {
        $translation = factory(Post::class)
            ->state('english')
            ->with(2, 'master.meta')
            ->create();

        // Relation
        $this->assertEquals(2, $translation->meta()->count());
        $this->assertEquals(0, $translation->meta()->withoutLanguageScope()->count());

        // Eager load
        $this->assertEquals(2, $translation->load('meta')->meta->count());
        $this->assertEquals(0, $translation->load(['meta' => function ($query) {
            $query->withoutLanguageScope();

            // Let's also check if the query actually returns any results, but just didn't
            // match on model hydration!
            $this->assertEquals(0, $query->count());
        }])->meta->count());
    }

//    TODO make this test pass
//
//    /** @test * */
//    public function regression_order_does_not_matter_when_using_with_count_with_language()
//    {
//        $category = factory(Category::class)
//            ->with(1, 'english', 'posts.translations')
//            ->with(2, 'posts.translations.meta')
//            ->create();
//
//        $results = Category::whereKey($category->id)->with(['posts' => function ($query) {
//            $query->withCount('meta');
//            $query->language('en'); // it works if language is invoked before withCount
//        }])->get();
//
//        $this->assertEquals(1, $results[0]->posts->count());
//    }

    /**
     * @param $lang
     * @return \Closure
     */
    protected function ofLanguage($lang)
    {
        return function ($query) use ($lang) {
            return $query->language($lang);
        };
    }
}
