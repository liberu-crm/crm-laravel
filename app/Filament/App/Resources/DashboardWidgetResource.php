<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DashboardWidgetResource\Pages;
use App\Models\DashboardWidget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class DashboardWidgetResource extends Resource
{
    protected static ?string $model = DashboardWidget::class;

    protected static ?string $navigationIcon = 'heroicon-o-template';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('widget_type')
                    ->required(),
                Forms\Components\TextInput::make('position')
                    ->integer()
                    ->required(),
                Forms\Components\KeyValue::make('settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('widget_type'),
                Tables\Columns\TextColumn::make('position'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListDashboardWidgets::route('/'),
            'create' => Pages\CreateDashboardWidget::route('/create'),
            'edit' => Pages\EditDashboardWidget::route('/{record}/edit'),
        ];
    }
}