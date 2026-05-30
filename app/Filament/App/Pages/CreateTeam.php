<?php

namespace App\Filament\App\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateTeam extends RegisterTenant
{
    protected string $view = 'filament.pages.create-team';

    public string $name = '';

    protected Width|string|null $maxWidth = '2xl';

    public function mount(): void
    {
        // abort_unless(Filament::auth()->user()->canCreateTeams(), 403);
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

    protected function handleRegistration(array $data): Model
    {
        return app(\App\Actions\Jetstream\CreateTeam::class)->create(auth()->user(), $data);
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Create Team',
        ];
    }

    public static function getLabel(): string
    {
        return 'Create Team';
    }
}
