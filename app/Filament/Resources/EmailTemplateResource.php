<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Use {{variable_name}} for dynamic content'),

                        Forms\Components\Select::make('category')
                            ->options([
                                'welcome' => 'Welcome',
                                'follow_up' => 'Follow Up',
                                'newsletter' => 'Newsletter',
                                'promotional' => 'Promotional',
                                'transactional' => 'Transactional',
                                'notification' => 'Notification',
                            ])
                            ->searchable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Email Content')
                    ->schema([
                        Forms\Components\Textarea::make('body')
                            ->label('Plain Text Body')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('Fallback for email clients that don\'t support HTML'),

                        Forms\Components\RichEditor::make('html_body')
                            ->label('HTML Body')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                            ])
                            ->helperText('Use {{variable_name}} for dynamic content'),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\TagsInput::make('variables')
                            ->label('Available Variables')
                            ->helperText('Define variables that can be used in this template')
                            ->placeholder('Add variable names'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'welcome' => 'primary',
                        'follow_up' => 'success',
                        'promotional' => 'warning',
                        'newsletter' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'welcome' => 'Welcome',
                        'follow_up' => 'Follow Up',
                        'newsletter' => 'Newsletter',
                        'promotional' => 'Promotional',
                        'transactional' => 'Transactional',
                        'notification' => 'Notification',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All templates')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'view' => Pages\ViewEmailTemplate::route('/{record}'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
