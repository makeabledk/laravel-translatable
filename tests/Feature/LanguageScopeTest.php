<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\TestCase;

class LanguageScopeTest extends TestCase
{
    /** @test **/
    public function when_using_language_scope_it_finds_the_best_matching_model()
    {
        $this->seedTranslatedModels();

        $posts = Post::language(['sv', 'en'])->get();

        $this->assertEquals(2, $posts->count());

        [$en, $sv] = [$posts->get(0), $posts->get(1)];

        $this->assertEquals('en', $en->language_code);
        $this->assertEquals('sv', $sv->language_code);

        // Since they are both translations of each their post, they should have a master_id set
        $this->assertEquals('da', Post::findOrFail($en->master_id)->language_code);
        $this->assertEquals('da', Post::findOrFail($sv->master_id)->language_code);
    }

    /** @test **/
    public function it_can_fallback_to_master_when_no_language_matches()
    {
        $this->seedTranslatedModels();

        $posts = Post::language(['sv', 'en'], true)->get();

        $this->assertEquals(3, $posts->count());

        [$da, $en, $sv] = [$posts->get(0), $posts->get(1), $posts->get(2)];

        $this->assertEquals('da', $da->language_code);
        $this->assertEquals('en', $en->language_code);
        $this->assertEquals('sv', $sv->language_code);

        // This should give us the exact same thing
        $this->assertEquals(3, Post::language(['sv', 'en', '*'])->get()->count());
    }

    /** @test **/
    public function it_applies_global_scopes_on_best_ids_query()
    {
        Post::addGlobalScope(function ($query) {
            $query->where('is_published', 1);
        });

        $post = factory(Post::class)
            ->with(1, 'english', 'translations', ['is_published' => 0])
            ->create();

        $match = Post::language(['en', '*'])->whereMasterKey($post->id)->first();

        $this->assertNotNull($match);
        $this->assertEquals('da', $post->language_code);
    }

    protected function seedTranslatedModels()
    {
        factory(Post::class)->create(); // danish

        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->create();

        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->create();
    }
}
