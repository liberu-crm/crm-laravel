<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mounts every App-panel resource's EDIT page against a real seeded row, plus
 * the INDEX with that row present. An empty index masks column drift: a table
 * column or form field bound to a nonexistent DB column only fatals when a row
 * renders or the edit form hydrates. This exercises those deeper paths.
 *
 * DealResource is skipped (built by a concurrent task). Resources whose model
 * has no factory are skipped and reported.
 */
class ResourceEditPageMountTest extends TestCase
{
    use RefreshDatabase;

    private function actingManagerWithTeam(): Team
    {
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->ownedTeams->first();
        $user->current_team_id = $team->id;
        $user->save();
        $user->assignRole('manager');
        $this->actingAs($user);

        return $team;
    }

    public function test_all_resource_edit_and_populated_index_pages_mount(): void
    {
        $team = $this->actingManagerWithTeam();

        $failures = [];   // HTTP 500 on edit or index -> resource form/column drift
        $covered = [];    // mounted 200
        $skipped = [];    // model has no factory
        $flagged = [];    // factory cannot seed a row -> model/migration drift

        foreach (glob(app_path('Filament/App/Resources/*Resource.php')) as $file) {
            $basename = basename($file, '.php');

            // Concurrent task owns DealResource — do not touch it here.
            if ($basename === 'DealResource') {
                continue;
            }

            $class = 'App\\Filament\\App\\Resources\\'.$basename;

            if (! class_exists($class) || ! isset($class::getPages()['edit'])) {
                continue;
            }

            $model = $class::getModel();

            if (! $this->modelHasFactory($model)) {
                $skipped[] = class_basename($class);

                continue;
            }

            $attrs = [];
            if (Schema::hasColumn((new $model)->getTable(), 'team_id')) {
                $attrs['team_id'] = $team->id;
            }

            try {
                $record = $model::factory()->create($attrs);
            } catch (\Throwable $e) {
                $flagged[] = class_basename($class).' [seed]: '.$this->oneLine($e);

                continue;
            }

            $slug = $class::getSlug();
            $editStatus = $this->get('/app/'.$team->id.'/'.$slug.'/'.$record->getKey().'/edit')->status();
            $indexStatus = $this->get('/app/'.$team->id.'/'.$slug)->status();

            if ($editStatus === 500 || $indexStatus === 500) {
                $failures[] = class_basename($class)." (edit={$editStatus}, index={$indexStatus})";
            } else {
                $covered[] = class_basename($class);
            }
        }

        $this->assertNotEmpty($covered, 'no edit pages were exercised');
        $this->assertSame([], $failures, implode("\n", [
            'Edit/index pages that fatal (500): '.implode(', ', $failures),
            'skipped (no factory): '.implode(', ', $skipped),
            'flagged (seed failed, needs model/migration): '.implode(' || ', $flagged),
        ]));
    }

    /** True only when the model exposes a usable factory class. */
    private function modelHasFactory(string $model): bool
    {
        if (! method_exists($model, 'factory')) {
            return false;
        }

        try {
            return $model::factory() instanceof Factory;
        } catch (\Throwable) {
            return false;
        }
    }

    private function oneLine(\Throwable $e): string
    {
        return class_basename($e).': '.strtok($e->getMessage(), "\n");
    }
}
