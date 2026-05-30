<?php

namespace App\Filament\App\Pages;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTeam extends EditTenantProfile
{
    protected string $view = 'filament.pages.edit-team';

    public string $name = '';

    public static function getLabel(): string
    {
        return 'Edit Team';
    }

    public function mount(): void
    {
        abort_unless($this->user()->canCreateTeams(), 403);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Team Name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function submit(): mixed
    {
        $this->validate();

        $team = Team::forceCreate([
            'user_id' => Filament::auth()->id(),
            'name' => $this->name,
            'personal_team' => false,
        ]);

        $this->user()->teams()->attach($team, ['role' => 'admin']);
        $this->user()->switchTeam($team);

        return redirect()->route('filament.pages.edit-team', ['team' => $team]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Edit Team',
        ];
    }

    private function user(): User
    {
        return Filament::auth()->user();
    }
}
