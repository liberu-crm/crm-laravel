<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\DocumentResource\Pages\ListDocuments;
use App\Models\Contact;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $slug = 'documents';

    /**
     * A customer sees only the documents attached to their own Contact (matched
     * by email + team via the onboarding link). Document is not IsTenantModel, so
     * the team filter is applied explicitly. A null contact id yields zero rows.
     */
    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('team_id', self::currentTeamId())
            ->where('documentable_type', Contact::class)
            ->where('documentable_id', self::customerContactId());
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Document $record, DocumentService $service) => $service->download($record)),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
        ];
    }

    private static function currentTeamId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? $user->getAttribute('current_team_id') : null;
    }

    private static function customerContactId(): ?int
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        return Contact::query()
            ->where('team_id', $user->getAttribute('current_team_id'))
            ->where('email', $user->getAttribute('email'))
            ->value('id');
    }
}
