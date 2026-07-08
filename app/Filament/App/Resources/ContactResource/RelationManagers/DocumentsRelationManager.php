<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ContactResource\RelationManagers;

use App\Actions\Portal\ShareDocumentWithContact;
use App\Models\Contact;
use App\Models\Document;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Staff attach documents to a Contact; they surface in that customer's portal
 * document browse (#484). Lives on the tenant-scoped app panel, so staff only
 * reach their own team's Contacts.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                Action::make('share')
                    ->label('Share document')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        // storeFiles(false) hands the raw upload to the action so
                        // DocumentService::upload runs its mime allowlist + storeFile.
                        FileUpload::make('file')->storeFiles(false)->required(),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('type')->maxLength(255),
                    ])
                    ->action(function (array $data, ShareDocumentWithContact $share): void {
                        /** @var Contact $contact */
                        $contact = $this->getOwnerRecord();
                        $share($contact, $data['file'], $data['name'], $data['type'] ?? null);
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Document $record, DocumentService $service) => $service->download($record)),
                DeleteAction::make()
                    ->using(fn (Document $record, DocumentService $service): bool => tap(true, fn () => $service->delete($record))),
            ]);
    }
}
