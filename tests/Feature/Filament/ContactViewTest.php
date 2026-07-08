<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\ContactResource\Pages\ViewContact;
use App\Models\Contact;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Contact}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        $territory = Territory::factory()->create(['team_id' => $team->id]);
        setPermissionsTeamId($team->id);
        $user->assignRole($role);
        $user->territories()->attach($territory);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'territory_id' => $territory->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'phone_number' => '+15551234567',
            'company_id' => null,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $contact];
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        [, $contact] = $this->setUpViewer('admin');

        Livewire::test(ViewContact::class, ['record' => $contact->getKey()])
            ->assertOk()
            ->assertSee('Jane');
    }

    public function test_free_role_sees_masked_sensitive_fields(): void
    {
        [, $contact] = $this->setUpViewer('free');

        Livewire::test(ViewContact::class, ['record' => $contact->getKey()])
            ->assertOk()
            ->assertSee('[hidden]')
            ->assertDontSee('jane@example.com')
            ->assertDontSee('+15551234567');
    }

    public function test_manager_sees_real_values(): void
    {
        [, $contact] = $this->setUpViewer('manager');

        Livewire::test(ViewContact::class, ['record' => $contact->getKey()])
            ->assertOk()
            ->assertSee('jane@example.com');
    }
}
