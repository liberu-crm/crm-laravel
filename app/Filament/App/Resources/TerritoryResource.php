<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Enums\Role;
use App\Filament\App\Resources\TerritoryResource\Pages\CreateTerritory;
use App\Filament\App\Resources\TerritoryResource\Pages\EditTerritory;
use App\Filament\App\Resources\TerritoryResource\Pages\ListTerritories;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Manage a team's sales territories and their members (G3 ABAC foundation).
 * Territory is IsTenantModel, so it auto-scopes to the current team.
 */
class TerritoryResource extends Resource
{
    protected static ?string $model = Territory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Territories';

    protected static ?string $slug = 'territories';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole([Role::SuperAdmin, Role::Admin, Role::Manager]);
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('users')
                ->label('Members')
                ->multiple()
                ->preload()
                // Only the current team's members are assignable.
                ->relationship('users', 'name', fn (Builder $query): Builder => $query->whereIn('users.id', self::teamMemberIds())),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('users_count')->counts('users')->label('Members'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTerritories::route('/'),
            'create' => CreateTerritory::route('/create'),
            'edit' => EditTerritory::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private static function teamMemberIds(): array
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Team ? $tenant->allUsers()->pluck('id')->all() : [];
    }
}
