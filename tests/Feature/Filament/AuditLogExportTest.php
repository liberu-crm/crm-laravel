<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Filament\Exports\AuditLogExporter;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function actAsAdmin(): User
    {
        $admin = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $admin->currentTeam;
        setPermissionsTeamId($team->id);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return $admin;
    }

    public function test_exporter_defines_expected_columns(): void
    {
        $names = array_map(
            fn ($column): string => $column->getName(),
            AuditLogExporter::getColumns(),
        );

        $this->assertNotEmpty($names);
        foreach (['created_at', 'user.name', 'action', 'auditable_type', 'auditable_id', 'ip_address', 'description'] as $expected) {
            $this->assertContains($expected, $names);
        }

        // changes is deliberately out of scope for CSV.
        $this->assertNotContains('changes', $names);
    }

    public function test_list_page_exposes_export_header_action(): void
    {
        $this->actAsAdmin();

        Livewire::test(ListAuditLogs::class)
            ->assertOk()
            ->assertTableHeaderActionsExistInOrder(['export']);
    }
}
