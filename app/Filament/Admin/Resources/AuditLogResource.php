<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Filament\Admin\Resources\AuditLogResource\Pages\ViewAuditLog;
use App\Filament\Admin\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('user.name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('action')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->maxLength(65535),
                TextInput::make('ip_address')
                    ->required()
                    ->maxLength(45),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name'),
                TextColumn::make('action'),
                TextColumn::make('description'),
                TextColumn::make('ip_address'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
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
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}