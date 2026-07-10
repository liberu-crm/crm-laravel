<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AdSetResource;
use App\Filament\App\Resources\AdSetResource\Pages\ListAdSets;
use App\Filament\Exports\AdSetExporter;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdSetExportTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $role): User
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $user;
    }

    public function test_exports_table_exists_and_exporter_has_columns(): void
    {
        $this->actAs('admin');
        $this->assertTrue(Schema::hasTable('exports'));
        $this->assertNotEmpty(AdSetExporter::getColumns());
    }

    public function test_admin_sees_export_action(): void
    {
        $this->actAs('admin');
        Livewire::test(ListAdSets::class)
            ->assertOk()
            ->assertTableHeaderActionsExistInOrder(['export']);
    }

    public function test_free_role_cannot_access_ad_sets(): void
    {
        $this->actAs('free');
        // free (advertising = no access under enforcement) can't reach the AdSet
        // list at all, so there is no export path to gate.
        $this->assertFalse(AdSetResource::canViewAny());
    }
}
