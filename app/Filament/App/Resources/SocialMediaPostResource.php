<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
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
                                'youtube' => 'YouTube',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(SocialMediaPost::getStatuses())
                            ->required(),
                    ])
                    ->columnSpan(2),
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('Analytics')
                            ->content(function (SocialMediaPost $record) {
                                return view('filament.components.social-media-analytics', ['post' => $record]);
                            }),
                    ])
                    ->columnSpan(1)
                    ->hidden(fn ($record) => $record === null || $record->status !== SocialMediaPost::STATUS_PUBLISHED),
            ])
            ->columns(3);
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
                Tables\Columns\TextColumn::make('likes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shares')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SocialMediaPost::getStatuses()),
                Tables\Filters\SelectFilter::make('platforms')
                    ->options([
                        'facebook' => 'Facebook',
                        'linkedin' => 'LinkedIn',
                        'twitter' => 'Twitter/X',
                        'instagram' => 'Instagram',
                        'youtube' => 'YouTube',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewSocialMediaPost::route('/{record}'),
            'edit' => Pages\EditSocialMediaPost::route('/{record}/edit'),
        ];
    }
}