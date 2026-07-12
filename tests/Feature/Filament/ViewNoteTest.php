<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\NoteResource\Pages\ViewNote;
use App\Models\Note;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewNoteTest extends TestCase
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

    public function test_admin_can_mount_the_view_page_and_see_note_content(): void
    {
        $user = $this->actAs('admin');

        $note = Note::factory()->create([
            'team_id' => $user->currentTeam->id,
            'content' => 'Called the client about renewal',
        ]);

        Livewire::test(ViewNote::class, ['record' => $note->getKey()])
            ->assertOk()
            ->assertSee('Called the client about renewal');
    }
}
