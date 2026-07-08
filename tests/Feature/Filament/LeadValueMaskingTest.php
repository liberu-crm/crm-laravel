<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\LeadResource\Pages\EditLead;
use App\Filament\App\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadValueMaskingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Lead}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        // Owner-scoped: the lead must belong to the viewer to be visible to a rep/free.
        $lead = Lead::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'potential_value' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $lead];
    }

    public function test_free_user_serialization_masks_value_without_mutation(): void
    {
        [, $lead] = $this->setUpViewer('free');
        $fresh = $lead->fresh();

        $this->assertSame('[hidden]', $fresh->toArray()['potential_value']);
        // Direct access is unmasked (no corruption).
        $this->assertSame('50000.00', (string) $fresh->potential_value);
    }

    public function test_manager_serialization_shows_real_value(): void
    {
        [, $lead] = $this->setUpViewer('manager');

        $this->assertSame('50000.00', (string) $lead->fresh()->toArray()['potential_value']);
    }

    public function test_free_user_sees_masked_value_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListLeads::class)
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_free_user_value_field_is_hidden_on_edit(): void
    {
        [, $lead] = $this->setUpViewer('free');

        Livewire::test(EditLead::class, ['record' => $lead->getKey()])
            ->assertFormFieldIsHidden('potential_value')
            ->assertSee('[hidden]');
    }

    public function test_manager_sees_the_real_value_on_edit(): void
    {
        [, $lead] = $this->setUpViewer('manager');

        Livewire::test(EditLead::class, ['record' => $lead->getKey()])
            ->assertFormSet(['potential_value' => '50000.00']);
    }
}
