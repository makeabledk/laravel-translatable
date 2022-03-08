<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Makeable\LaravelTranslatable\Tests\Stubs\Image;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\Tag;
use Makeable\LaravelTranslatable\Tests\Stubs\User;
use Makeable\LaravelTranslatable\Tests\TestCase;

class MorphToTest extends TestCase
{
    /** @test **/
    public function it_can_save_and_access_translated_morph_to_relationships_from_translated_child()
    {
        $postMaster = factory(Post::class)
            ->with(1, 'english', 'translations')
            ->count(2)
            ->create()
            ->last(); // Ensure the post() relation on Meta doesn't just select the first post in table, but actually matches foreign key constraints

        $tagMaster = factory(Tag::class)->create();
        $tagTranslation = $tagMaster->getTranslationOrNew('en');
        $tagTranslation->taggable()->associate($postTranslation = $postMaster->getTranslation('en'))->save();

        $this->assertEquals($postMaster->getMorphClass(), $tagTranslation->taggable_type);
        $this->assertEquals($postMaster->getMorphClass(), $tagMaster->refresh()->taggable_type);

        $this->assertEquals($postMaster->id, $tagTranslation->taggable_id, 'It sets the master id');
        $this->assertEquals($postTranslation->id, $tagTranslation->taggable->id, 'When no locale set, it defaults to current locale of child');
        $this->assertEquals('en', $tagTranslation->taggable->locale, 'When no locale set, it defaults to current locale of child');

        $this->assertEquals(1, $tagTranslation->taggable()->count(), 'It always just matches 1 parent at a time');
        $this->assertEquals(1, $tagTranslation->taggable()->locale('da')->count());
        $this->assertEquals($postMaster->id, $tagTranslation->taggable()->locale('da')->first()->id, 'Another locale may be set on the relation which changes the outcome');
    }

    /** @test **/
    public function it_can_load_nested_translatable_morph_to_relations()
    {
        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'tags')
            ->with(1, 'english', 'tags.translations')
            ->count(2)
            ->create();

        $load = function ($locale) {
            return Post::latest()
                ->locale($locale)
                ->with('tags.taggable')
                ->first()
                ->toArray();
        };

        $result = $load('en');

        $this->assertEquals(1, count(data_get($result, 'tags')));
        $this->assertEquals('en', data_get($result, 'locale'));
        $this->assertEquals('en', data_get($result, 'tags.0.locale'));
        $this->assertEquals('en', data_get($result, 'tags.0.taggable.locale'));

        $result = $load('sv');
        $this->assertEquals(1, count(data_get($result, 'tags')));
        $this->assertEquals('sv', data_get($result, 'locale'));
        $this->assertEquals('da', data_get($result, 'tags.0.locale'), 'Fallback to master (da)');
        $this->assertEquals('sv', data_get($result, 'tags.0.taggable.locale'));
    }

    /** @test **/
    public function locale_scope_can_be_disabled_for_morph_to()
    {
        $user = factory(User::class)->create();
        $user->photo()->associate(
            $master = factory(Post::class) // it doesn't matter which translated model we morph to in this example
                ->with(1, 'english', 'translations')
                ->create()
        )->save();

        $user->setRelations([]);

        Post::setGlobalLocale('en');

        $this->assertEquals('en', $user->photo()->first()->locale);
        $this->assertEquals('da', $user->photo()->withoutLocaleScope()->first()->locale);
    }

    /** @test **/
    public function regression_a_none_translatable_model_can_morph_to_translatable_parent()
    {
        $user = factory(User::class)->create();
        $user->photo()->associate(
            $master = factory(Post::class) // it doesn't matter which translated model we morph to in this example
                ->with(1, 'english', 'translations')
                ->create()
        )->save();

        $inEnglish = function ($model) {
            $model->locale('en');
        };

        $this->assertEquals('en', data_get($user->load(['photo' => $inEnglish]), 'photo.locale'));
    }

    /** @test **/
    public function regression_it_may_morph_to_a_combination_of_both_translated_and_nontranslatable_models()
    {
        factory(User::class)->create()->photo()->associate(factory(Post::class)->create())->save();
        factory(User::class)->create()->photo()->associate(factory(Image::class)->create())->save();

        $users = User::with('photo')->get();

        $this->assertInstanceOf(Post::class, $users->first()->photo);
        $this->assertInstanceOf(Image::class, $users->last()->photo);
    }

    /** @test **/
    public function regression_support_eager_loading_morph_constraints()
    {
        factory(Post::class)
            ->with(1, 'english', 'translations')
            ->andWith(1, 'swedish', 'translations')
            ->with(1, 'tags')
            ->with(1, 'english', 'tags.translations')
            ->with(1, 'meta', ['key' => 'foo'])
            ->with(1, 'english', 'meta.translations', ['key' => 'bar'])
            ->count(1)
            ->create();

        $load = function ($locale) {
            return Tag::query()
                ->locale($locale)
                ->with([
                    'taggable' => fn (MorphTo $query) => $query->constrain([
                        Post::class => fn ($query) => $query->with(['meta'])
                    ])
                ])
                ->first()
                ->toArray();
        };

        $result = $load('en');

        $this->assertEquals('en', data_get($result, 'locale'));
        $this->assertEquals(1, count(data_get($result, 'taggable.meta')));
        $this->assertEquals('en', data_get($result, 'taggable.meta.0.locale'));

        $result = $load('da');
        $this->assertEquals('da', data_get($result, 'locale'));
        $this->assertEquals(1, count(data_get($result, 'taggable.meta')));
        $this->assertEquals('da', data_get($result, 'taggable.meta.0.locale'));
    }
}
