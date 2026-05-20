<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\DashboardWidgetResource\Pages\ListDashboardWidgets;
use App\Filament\App\Resources\DashboardWidgetResource\Pages\CreateDashboardWidget;
use App\Filament\App\Resources\DashboardWidgetResource\Pages\EditDashboardWidget;
use App\Filament\App\Resources\DashboardWidgetResource\Pages;
use App\Models\DashboardWidget;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class DashboardWidgetResource extends Resource
{
    protected static ?string $model = DashboardWidget::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('widget_type')
                    ->required(),
                TextInput::make('position')
                    ->integer()
                    ->required(),
                KeyValue::make('settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name'),
                TextColumn::make('widget_type'),
                TextColumn::make('position'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListDashboardWidgets::route('/'),
            'create' => CreateDashboardWidget::route('/create'),
            'edit' => EditDashboardWidget::route('/{record}/edit'),
        ];
    }
}