<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Portal\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Support tickets';

    /**
     * A customer only ever sees the tickets they raised. Filament resolves both
     * table rows and single-record routes through this query, so an id the
     * customer does not own resolves to a 404 (no cross-customer disclosure).
     */
    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    /**
     * A customer can only raise a ticket once they belong to a tenant — the new
     * ticket's team_id comes from their current_team_id, so without one the
     * ticket could not route to any staff. Gates the button and the create route.
     */
    #[\Override]
    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User && filled($user->getAttribute('current_team_id'));
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        // Editable on the Create page; the View page (ViewRecord) renders it
        // read-only. Ticket routing fields (user_id/team_id/source/email_id/status)
        // are set server-side in CreateTicket, never exposed here.
        return $schema->components([
            TextInput::make('subject')->required()->maxLength(255),
            Textarea::make('body')->label('Description')->required()->columnSpanFull(),
            Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->default('medium')
                ->required(),
            FileUpload::make('attachment')
                ->disk('local')
                ->directory('ticket-attachments')
                ->visibility('private')
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                ->maxSize(5120),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}
