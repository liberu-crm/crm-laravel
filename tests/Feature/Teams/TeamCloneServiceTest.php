<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Contact;
use App\Models\Menu;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamCloneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamCloneServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Source team with the remap shapes that exist in the schema: a
     * Stage -> Pipeline tree edge and a self-referential Menu parent/child,
     * plus a trivial config row.
     *
     * @return array{Team, int, int} source team, source pipeline id, source stage id
     */
    private function seedSource(): array
    {
        $source = Team::factory()->create();

        $pipeline = Pipeline::factory()->create(['team_id' => $source->id]);
        $stage = Stage::factory()->create(['team_id' => $source->id, 'pipeline_id' => $pipeline->id]);

        $parent = Menu::factory()->create(['team_id' => $source->id]);
        Menu::factory()->create(['team_id' => $source->id, 'parent_id' => $parent->id]); // self ref

        Tag::factory()->create(['team_id' => $source->id, 'name' => 'VIP']); // trivial config

        return [$source, $pipeline->id, $stage->id];
    }

    public function test_clones_config_and_remaps_foreign_keys(): void
    {
        [$source, $sourcePipelineId] = $this->seedSource();
        $owner = User::factory()->create();

        $new = (new TeamCloneService)->clone($source, 'Cloned Team', $owner);

        $this->assertSame('Cloned Team', $new->name);
        $this->assertSame($owner->id, $new->user_id);
        $this->assertFalse((bool) $new->personal_team);
        $this->assertNotSame($source->id, $new->id);

        $newPipeline = DB::table('pipelines')->where('team_id', $new->id)->first();
        $newStage = DB::table('stages')->where('team_id', $new->id)->first();

        // New PK, and Stage.pipeline_id rewired to the CLONED pipeline.
        $this->assertNotSame($sourcePipelineId, (int) $newPipeline->id);
        $this->assertSame((int) $newPipeline->id, (int) $newStage->pipeline_id);

        // Self-referential menu rewired to the cloned parent.
        $newParent = DB::table('menus')->where('team_id', $new->id)->whereNull('parent_id')->first();
        $newChild = DB::table('menus')->where('team_id', $new->id)->whereNotNull('parent_id')->first();
        $this->assertSame((int) $newParent->id, (int) $newChild->parent_id);

        // Trivial config carried over.
        $this->assertDatabaseHas('tags', ['team_id' => $new->id, 'name' => 'VIP']);
    }

    public function test_does_not_clone_transactional_data(): void
    {
        [$source] = $this->seedSource();
        Contact::factory()->create(['team_id' => $source->id]);
        $owner = User::factory()->create();

        $new = (new TeamCloneService)->clone($source, 'No Data', $owner);

        $this->assertSame(0, DB::table('contacts')->where('team_id', $new->id)->count());
    }

    public function test_source_team_is_untouched(): void
    {
        [$source, $pipelineId, $stageId] = $this->seedSource();
        $owner = User::factory()->create();

        (new TeamCloneService)->clone($source, 'Clone', $owner);

        $this->assertSame(1, DB::table('pipelines')->where('team_id', $source->id)->count());
        $this->assertSame($pipelineId, (int) DB::table('stages')->where('id', $stageId)->first()->pipeline_id);
    }
}
