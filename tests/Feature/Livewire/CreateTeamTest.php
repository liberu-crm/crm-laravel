<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CreateTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Events\AddingTeam;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_team_form_mounts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(CreateTeam::class)
            ->assertStatus(200);
    }

    public function test_creating_a_team_persists_it_for_the_user(): void
    {
        \Event::fake([AddingTeam::class]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateTeam::class)
            ->set('state.name', 'Acme Corp')
            ->call('createTeam');

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme Corp',
            'user_id' => $user->id,
            'personal_team' => true,
        ]);

        \Event::assertDispatched(AddingTeam::class);
    }
}
