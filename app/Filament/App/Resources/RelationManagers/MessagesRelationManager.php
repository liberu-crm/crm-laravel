<?php

namespace App\Filament\App\Resources\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Messages';
    protected static ?string $recordTitleAttribute = 'content';

    public function form(Schema $schema): Schema
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender')
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
                TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'replied' => 'Replied',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
