<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use App\Services\FacebookAdsService;
use App\Services\LinkedInAdsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;

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
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required(),
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
                        Forms\Components\TextInput::make('link')
                            ->url()
                            ->label('Link (optional)'),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->label('Image (optional)')
                            ->disk('public')
                            ->directory('social-media-posts'),
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
                Tables\Actions\Action::make('publish')
                    ->action(function (SocialMediaPost $record) {
                        self::publishPost($record);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (SocialMediaPost $record) => $record->status === SocialMediaPost::STATUS_SCHEDULED),
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
        ];
    }

    protected static function publishPost(SocialMediaPost $post)
    {
        foreach ($post->platforms as $platform) {
            try {
                switch ($platform) {
                    case 'facebook':
                        $facebookService = new FacebookAdsService($post->advertising_account);
                        $result = $facebookService->createAndSchedulePost($post->advertising_account->account_id, [
                            'message' => $post->content,
                            'scheduled_time' => $post->scheduled_at->timestamp,
                            'link' => $post->link,
                            'image' => $post->image,
                        ]);
                        $post->platform_post_ids['facebook'] = $result['post_id'];
                        break;
                    case 'linkedin':
                        $linkedInService = new LinkedInAdsService($post->advertising_account);
                        $result = $linkedInService->createAndSchedulePost($post->advertising_account->account_id, [
                            'message' => $post->content,
                            'scheduled_time' => $post->scheduled_at->format('c'),
                            'link' => $post->link,
                        ]);
                        $post->platform_post_ids['linkedin'] = $result['id'];
                        break;
                    // Add cases for other platforms as needed
                }
            } catch (\Exception $e) {
                Log::error("Failed to publish post to $platform: " . $e->getMessage());
                $post->status = SocialMediaPost::STATUS_FAILED;
                $post->save();
                return;
            }
        }

        $post->status = SocialMediaPost::STATUS_PUBLISHED;
        $post->save();
    }
}
