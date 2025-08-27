<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\RelationManagers\MessagesRelationManager;
use App\Filament\App\Resources\TicketResource\Pages\ListTickets;
use App\Filament\App\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\App\Resources\TicketResource\Pages\EditTicket;
use App\Filament\App\Resources\TicketResource\Pages;
use App\Models\Ticket;
use App\Services\UnifiedHelpDeskService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Cache;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';
    protected static string | \UnitEnum | null $navigationGroup = 'Help Desk';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')->required(),
                Textarea::make('body')->required(),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                    ])
                    ->required(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->required(),
                Select::make('source')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ])
                    ->required(),
                TextInput::make('source_id')
                    ->label('Source ID (Email ID or Contact Number)')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('source_id'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reply')
                    ->action(function (Ticket $record, array $data, UnifiedHelpDeskService $helpDeskService) {
                        try {
                            $helpDeskService->sendReply(
                                $record->source_id,
                                $data['reply_content'],
                                $record->source,
                                $record->account_id
                            );

                            $record->update(['status' => 'in_progress']);
                            Cache::tags(['messages'])->flush();

                            Notification::make()
                                ->title('Reply sent successfully')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error sending reply')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->schema([
                        Textarea::make('reply_content')
                            ->required()
                            ->label('Reply'),
                    ]),
                Action::make('view_messages')
                    ->url(fn (Ticket $record) => MessageResource::getUrl('index', [
                        'tableFilters[source_id][value]' => $record->source_id
                    ]))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->label('View Messages'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->refreshable()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}