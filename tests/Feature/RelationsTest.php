<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelFactory\Factory;
use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class RelationsTest extends TestCase
{
    /** @test **/
    public function it_merges_translations_according_to_given_priority()
    {
        $this->seedTranslatedModels();

        $posts = Post::requestLanguagePriority(['sv', 'en'])->get();

        $this->assertEquals(2, $posts->count());

        [$en, $sv] = [$posts->get(0), $posts->get(1)];

        $this->assertEquals('en', $en->language_code);
        $this->assertEquals('sv', $sv->language_code);

        // Since they are both translations of each their course, they should have a master_id set
        $this->assertEquals('da', Post::findOrFail($en->master_id)->language_code);
        $this->assertEquals('da', Post::findOrFail($sv->master_id)->language_code);
    }

    /** @test **/
    public function it_can_fallback_to_master_when_no_language_matches()
    {
        $this->seedTranslatedModels();

        $posts = Post::requestLanguagePriority(['sv', 'en'], true)->get();

        $this->assertEquals(3, $posts->count());

        [$da, $en, $sv] = [$posts->get(0), $posts->get(1), $posts->get(2)];

        $this->assertEquals('da', $da->language_code);
        $this->assertEquals('en', $en->language_code);
        $this->assertEquals('sv', $sv->language_code);

        // This should give us the exact same thing
        $this->assertEquals(3, Post::requestLanguagePriority(['sv', 'en', '*'])->get()->count());
    }

    /** @test **/
    public function it_can_eager_load_translated_belongs_to_many_relationships()
    {
        $masterPost = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();

        $masterPost->images()->attach($image = factory(Image::class)->create());

        $image = Image::whereKey($image->id)->with(['posts' => function ($query) {
            $query->requestLanguagePriority('sv');
        }])->first();

        $this->assertEquals(1, $image->posts->count());
        $this->assertEquals('sv', $image->posts->first()->language_code);
    }

    /** @test **/
    public function it_can_eager_load_belongs_to_many_from_translatable_model()
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

        $result = $image->load([
            'posts' => function ($posts) {
                $posts->requestLanguagePriority('en');
            },
            'posts.images',
            'posts.images.posts' => function ($posts) {
                $posts->requestLanguagePriority('en');
            },
        ])->toArray();

        $this->assertEquals(1, count(data_get($result, 'posts')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.images')));
        $this->assertEquals(1, count(data_get($result, 'posts.0.images.0.posts')));
        $this->assertEquals('en', data_get($result, 'posts.0.language_code'));
        $this->assertEquals('en', data_get($result, 'posts.0.images.0.posts.0.language_code'));
    }

    protected function seedTranslatedModels()
    {
        factory(Post::class) // danish
            ->create();

        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();
    }
}
