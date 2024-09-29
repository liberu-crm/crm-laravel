<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(65535),
                Forms\Components\DateTimePicker::make('scheduled_at'),
                Forms\Components\MultiSelect::make('platforms')
                    ->options([
                        'facebook' => 'Facebook',
                        'linkedin' => 'LinkedIn',
                        'twitter' => 'Twitter/X',
                        'instagram' => 'Instagram',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(SocialMediaPost::getStatuses())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\TagsColumn::make('platforms'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'draft',
                        'warning' => 'scheduled',
                        'success' => 'published',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSocialMediaPosts::route('/'),
            'create' => Pages\CreateSocialMediaPost::route('/create'),
            'edit' => Pages\EditSocialMediaPost::route('/{record}/edit'),
            'view' => Pages\ViewSocialMediaPost::route('/{record}'),
        ];
    }
}