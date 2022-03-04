<?php

namespace Makeable\LaravelTranslatable\Tests\Feature;

use Illuminate\Support\Arr;
use Makeable\LaravelTranslatable\Tests\Stubs\Post;
use Makeable\LaravelTranslatable\Tests\Stubs\Team;
use Makeable\LaravelTranslatable\Tests\TestCase;

class SyncMasterAttributesTest extends TestCase
{
    /** @test **/
    public function it_fills_master_attributes_for_new_translations()
    {
        $team = factory(Team::class)->create();

        $danish = factory(Post::class)->create(['team_id' => $team->id]);
        $danish->translations()->save($english = factory(Post::class)->apply('english')->make());

        $this->assertEquals($team->id, $english->team_id);
    }

    /** @test **/
    public function it_can_tell_which_sync_attributes_has_been_changed()
    {
        // On master
        $master = factory(Post::class)->with('team')->create();
        $master->team_id = $newId = factory(Team::class)->create()->id;
        $master->save();

        $this->assertEquals(1, count($master->getChangedSyncAttributes()));
        $this->assertEquals($newId, Arr::get($master->getChangedSyncAttributes(), 'team_id'));

        // On newly created translation
        $master = factory(Post::class)->with('team')->create();
        $translation = factory(Post::class)->apply('english')->make();
        $translation->master_id = $master->id;
        $translation->team_id = $newId = factory(Team::class)->create()->id;
        $translation->save();

        $this->assertEquals(0, count($translation->getChangedSyncAttributes())); // Since it's just created, there is nothing to compare
        $this->assertEquals(1, count($translation->getChangedSyncAttributes($master->getAttributes()))); // Explicitly pass attributes to compare against
        $this->assertEquals($newId, Arr::get($translation->getChangedSyncAttributes($master->getAttributes()), 'team_id'));
    }

    /** @test **/
    public function it_checks_for_changed_sync_attributes_when_creating_translations()
    {
        $team_1 = factory(Team::class)->create();
        $team_2 = factory(Team::class)->create();

        $danish = factory(Post::class)->create(['team_id' => $team_1->id]);
        $danish->translations()->save($english = factory(Post::class)->apply('english')->make(['team_id' => $team_2->id]));

        $this->assertEquals($team_2->id, $english->team_id);
        $this->assertEquals($team_2->id, $danish->refresh()->team_id);
    }

    /** @test **/
    public function it_updates_all_translations_when_sync_attributes_are_updated()
    {
        $team_1 = factory(Team::class)->create();
        $team_2 = factory(Team::class)->create();

        $danish = factory(Post::class)->create(['team_id' => $team_1->id]);
        $danish->translations()->save($english = factory(Post::class)->apply('english')->make());

        $english->forceFill(['team_id' => $team_2->id])->save();

        $this->assertEquals($team_2->id, $english->team_id);
        $this->assertEquals($team_2->id, $danish->refresh()->team_id);
    }

    /** @test **/
    public function it_detects_relationship_names_and_expands_them_to_foreign_keys()
    {
        $post = new Post;
        $post->sync = ['team'];

        $this->assertEquals(['team'], $post->sync);
        $this->assertEquals(['team_id'], $post->getSyncAttributeNames());
    }
}
