<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\BulkAction;
use App\Filament\App\Resources\MessageResource\Pages\ListMessages;
use App\Filament\App\Resources\MessageResource\Pages\CreateMessage;
use App\Filament\App\Resources\MessageResource\Pages\EditMessage;
use App\Filament\App\Resources\MessageResource\Pages;
use App\Models\Message;
use App\Services\UnifiedHelpDeskService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MessageResource extends Resource
{
    // protected static ?string $model = Message::class;
    protected static ?string $model = Message::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Help Desk';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'unread')->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sender')
                    ->required()
                    ->maxLength(255),
                Select::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ])
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'replied' => 'Replied',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Message ID')
                    ->searchable(),
                TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whatsapp' => 'success',
                        'facebook' => 'info',
                        'gmail' => 'danger',
                        'outlook' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('sender')
                    ->searchable(),
                TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'normal' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'replied' => 'Replied',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reply')
                    ->schema([
                        Textarea::make('reply_content')
                            ->required()
                            ->label('Reply'),
                    ])
                    ->action(function (Message $record, array $data, UnifiedHelpDeskService $helpDeskService) {
                        try {
                            $helpDeskService->sendReply(
                                $record->id,
                                $data['reply_content'],
                                $record->channel,
                                $record->account_id
                            );

                            $record->update(['status' => 'replied']);
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
                    }),
                Action::make('mark_as_read')
                    ->action(fn (Message $record) => $record->update(['status' => 'read']))
                    ->visible(fn (Message $record) => $record->status === 'unread'),
            ])
            ->toolbarActions([
                BulkAction::make('mark_as_read')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'read'])),
            ])
            ->defaultSort('timestamp', 'desc')
            ->refreshable()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'create' => CreateMessage::route('/create'),
            'edit' => EditMessage::route('/{record}/edit'),
        ];
    }
}
