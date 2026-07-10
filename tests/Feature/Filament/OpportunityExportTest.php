<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\OpportunityResource\Pages\ListOpportunities;
use App\Filament\Exports\OpportunityExporter;
use App\Models\User;
use App\Support\AccessContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityExportTest extends TestCase
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
        $this->assertNotEmpty(OpportunityExporter::getColumns());
    }

    public function test_admin_sees_export_action(): void
    {
        $this->actAs('admin');

        Livewire::test(ListOpportunities::class)
            ->assertOk()
            ->assertTableHeaderActionsExistInOrder(['export']);
    }

    public function test_free_role_export_action_is_hidden(): void
    {
        $this->actAs('free');
        $this->assertTrue(AccessContext::shouldMaskFields());

        $component = Livewire::test(ListOpportunities::class)->assertOk();
        $export = collect($component->instance()->getTable()->getHeaderActions())
            ->first(fn ($action): bool => $action->getName() === 'export');

        $this->assertNotNull($export);
        $this->assertFalse($export->isVisible());
    }
}
