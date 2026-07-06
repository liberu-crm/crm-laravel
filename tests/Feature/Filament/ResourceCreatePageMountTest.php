<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mounts every App-panel resource's create page. Unlike an empty index mount,
 * the create page instantiates the full form() schema — so a removed form
 * component or a form field bound to a nonexistent column fatals here.
 */
class ResourceCreatePageMountTest extends TestCase
{
    use RefreshDatabase;

    private function actingManagerWithTeam(): \App\Models\Team
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

    public function test_all_resource_create_pages_mount(): void
    {
        $team = $this->actingManagerWithTeam();
        $failures = [];
        $covered = [];

        foreach (glob(app_path('Filament/App/Resources/*Resource.php')) as $file) {
            $class = 'App\\Filament\\App\\Resources\\'.basename($file, '.php');

            if (! class_exists($class) || ! isset($class::getPages()['create'])) {
                continue;
            }

            $status = $this->get('/app/'.$team->id.'/'.$class::getSlug().'/create')->status();

            if ($status === 500) {
                $failures[] = class_basename($class);
            } else {
                $covered[] = class_basename($class);
            }
        }

        $this->assertNotEmpty($covered, 'no create pages were exercised');
        $this->assertSame([], $failures, 'Create pages that fatal (500): '.implode(', ', $failures));
    }
}
