<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\DealResource\Pages\ListDeals;
use App\Filament\Exports\DealExporter;
use App\Models\User;
use App\Support\AccessContext;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DealExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function actAs(string $role): User
    {
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $user;
    }

    public function test_exports_table_migration_ran(): void
    {
        $this->assertTrue(Schema::hasTable('exports'));
    }

    public function test_exporter_defines_columns(): void
    {
        $this->assertNotEmpty(DealExporter::getColumns());
    }

    public function test_admin_sees_export_header_action(): void
    {
        $this->actAs('admin');

        Livewire::test(ListDeals::class)
            ->assertOk()
            ->assertTableHeaderActionsExistInOrder(['export']);
    }

    public function test_free_role_is_denied_the_export(): void
    {
        $this->actAs('free');

        // Masking is active for the free role, so the export must be hidden —
        // a CSV would otherwise bypass the `value` masking.
        $this->assertTrue(AccessContext::shouldMaskFields());

        // getHeaderActions() returns actions regardless of visibility, so
        // assertTableHeaderActionsExistInOrder can't prove the gate. Pull the
        // export action and assert its visibility closure resolves to false.
        $component = Livewire::test(ListDeals::class)->assertOk();

        $export = null;
        foreach ($component->instance()->getTable()->getHeaderActions() as $action) {
            if ($action->getName() === 'export') {
                $export = $action;
                break;
            }
        }

        $this->assertNotNull($export, 'Export header action should be registered.');
        $this->assertFalse($export->isVisible(), 'Export must be hidden for the masked free role.');
    }
}
