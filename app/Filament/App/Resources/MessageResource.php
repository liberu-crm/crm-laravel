<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MessageResource\Pages;
use App\Models\Message;
use App\Services\UnifiedHelpDeskService;
use Filament\Forms;
use Filament\Forms\Form;
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
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Help Desk';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'unread')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sender')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('Message ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whatsapp' => 'success',
                        'facebook' => 'info',
                        'gmail' => 'danger',
                        'outlook' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'normal' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                        'gmail' => 'Gmail',
                        'outlook' => 'Outlook',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'replied' => 'Replied',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reply')
                    ->form([
                        Forms\Components\Textarea::make('reply_content')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error sending reply')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('mark_as_read')
                    ->action(fn (Message $record) => $record->update(['status' => 'read']))
                    ->visible(fn (Message $record) => $record->status === 'unread'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_as_read')
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
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
