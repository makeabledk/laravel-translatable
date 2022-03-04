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
        $master = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

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
            ->apply('english')
            ->with(2, 'master.meta', ['key' => 'foo'])
            ->create()
            ->load('meta');

        $this->assertEquals(2, $translation->meta->count());
        $this->assertEquals('en', $translation->locale);
        $this->assertEquals('foo', $translation->meta->first()->key);
    }

    /** @test **/
    public function the_has_many_translatable_models_always_selects_best_matching_locale()
    {
        $postMaster = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->count(2)
            ->create()
            ->first();

        $this->assertEquals(1, $postMaster->meta->count());
        $this->assertEquals('da', $postMaster->meta->first()->locale);

        $this->assertEquals(1, ($english = $postMaster->getTranslation('en'))->meta->count());
        $this->assertEquals('en', $english->meta->first()->locale);

        $this->assertEquals(1, ($swedish = $postMaster->getTranslation('sv'))->meta->count(), 'It should default to master when locale not available');
        $this->assertEquals('da', $swedish->meta->first()->locale, 'It should default to master when locale not available');
    }

    /** @test **/
    public function it_defaults_to_master_locale_when_parent_is_non_translatable()
    {
        $team = factory(Team::class)
            ->with(1, 'posts')
            ->with(1, 'english', 'posts.translations')
            ->create();

        $this->assertEquals(1, $team->posts->count(), 'Master version will be loaded unless a specific locale is requested');
        $this->assertEquals('da', $team->posts->first()->locale, 'Master version will be loaded unless a specific locale is requested');

        $this->assertEquals(1, $team->posts()->locale('en')->count(), 'A specific locale is requested');
        $this->assertEquals('en', $team->posts()->locale('en')->first()->locale, 'A specific locale is requested');
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
                $posts->locale('en');
            },
            'posts.meta',
        ])->toArray();

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.meta')));
        $this->assertEquals('en', data_get($result, 'posts.0.locale'));
        $this->assertEquals('en', data_get($result, 'posts.0.meta.0.locale'));
    }

    /** @test **/
    public function it_can_query_relation_existence_on_translated_has_many_relations()
    {
        $post = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        $this->assertEquals(1, Post::whereKey($post->id)->has('meta', '=', 1)->get()->count());
        $this->assertEquals(1, Post::whereKey($post->id)->whereHas('meta', $this->ofLocale('en'))->get()->count());
        $this->assertEquals(0, Post::whereKey($post->id)->whereHas('meta', $this->ofLocale('sv'))->get()->count());

        // On self (separate implementation)
        $this->assertEquals(1, Post::whereKey($post->id)->whereHas('translations', $this->ofLocale('en'))->get()->count());
        $this->assertEquals(0, Post::whereKey($post->id)->whereHas('translations', $this->ofLocale('sv'))->get()->count());

        // It also works on translated models
        $translations = $post->translations()->locale('en')->has('meta', '=', 1)->get();
        $this->assertEquals(1, $translations->count());
        $this->assertEquals('en', $translations->first()->locale);
    }

    /** @test * */
    public function regression_when_disabling_locale_scope_it_also_applies_to_with_count_method()
    {
        $post = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->with(1, 'meta')
            ->with(1, 'english', 'meta.translations')
            ->create();

        $this->assertEquals(1, Post::whereKey($post->id)->withCount('meta')->first()->meta_count);
        $this->assertEquals(2, Post::whereKey($post->id)->withCount('directMeta')->first()->direct_meta_count);
        $this->assertEquals(2, Post::whereKey($post->id)->withCount(['meta' => function ($meta) {
            $meta->withoutLocaleScope();
        }])->first()->meta_count);
    }

    /** @test * */
    public function has_many_locale_scope_may_be_disabled()
    {
        $translation = factory(Post::class)
            ->apply('english')
            ->with(2, 'master.meta')
            ->create();

        // Relation
        $this->assertEquals(2, $translation->meta()->count());
        $this->assertEquals(0, $translation->meta()->withoutLocaleScope()->count());

        // Eager load
        $this->assertEquals(2, $translation->load('meta')->meta->count());
        $this->assertEquals(0, $translation->load(['meta' => function ($query) {
            $query->withoutLocaleScope();

            // Let's also check if the query actually returns any results, but just didn't
            // match on model hydration!
            $this->assertEquals(0, $query->count());
        }])->meta->count());
    }

    /** @test * */
    public function regression_order_does_not_matter_when_using_with_count_with_locale()
    {
        // TODO: This regression fails again starting from laravel/framework v8.12.0
        // seemingly due to a core rewrite regarding QueriesRelationships@getRelationExistenceCountQuery.
        // Temporarily suppressed issue by locking framework version to <= 8.11.2.

        $category = factory(Category::class)
            ->with(1, 'english', 'posts.translations')
            ->with(2, 'posts.translations.meta')
            ->with(1, 'english', 'posts.translations.meta.translations')
            ->create();

        $category = Category::whereKey($category->id)->with(['posts' => function ($query) {
            $query->withCount('meta');
            $query->locale('en'); // it works if locale is invoked before withCount, however this order used to fail
        }])->first();

        $this->assertEquals(1, $category->posts->count());
        $this->assertEquals(2, $category->posts->first()->meta_count);
        $this->assertEquals('en', $category->posts->first()->locale);
    }

    /**
     * @param $lang
     * @return \Closure
     */
    protected function ofLocale($lang)
    {
        return function ($query) use ($lang) {
            return $query->locale($lang);
        };
    }
}
