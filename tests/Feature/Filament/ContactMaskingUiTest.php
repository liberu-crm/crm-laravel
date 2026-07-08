<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMaskingUiTest extends TestCase
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
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $contact];
    }

    public function test_mask_for_helper_masks_only_masked_fields_for_masked_roles(): void
    {
        [, $contact] = $this->setUpViewer('free');
        $fresh = $contact->fresh();

        $this->assertSame('[hidden]', $fresh->maskFor('email', $fresh->email));
        $this->assertSame('Jane', $fresh->maskFor('name', 'Jane'));
    }

    public function test_free_user_sees_masked_email_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListContacts::class)
            ->assertSee('[hidden]')
            ->assertDontSee('jane@example.com');
    }

    public function test_manager_sees_the_real_email_in_the_table(): void
    {
        $this->setUpViewer('manager');

        Livewire::test(ListContacts::class)
            ->assertSee('jane@example.com');
    }
}
