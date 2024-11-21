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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-share';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Post Content')
                            ->schema([
                                Forms\Components\Textarea::make('content')
                                    ->required()
                                    ->maxLength(65535)
                                    ->helperText('Write your post content here. Character limits vary by platform.')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => 
                                        $set('character_count', strlen($state))),
                                Forms\Components\TextInput::make('character_count')
                                    ->label('Character Count')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->required()
                                    ->helperText('Schedule when this post should be published')
                                    ->minDate(now())
                                    ->withoutSeconds(),
                                Forms\Components\MultiSelect::make('platforms')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'linkedin' => 'LinkedIn',
                                        'twitter' => 'Twitter/X',
                                        'instagram' => 'Instagram',
                                        'youtube' => 'YouTube',
                                    ])
                                    ->required()
                                    ->helperText('Select the platforms where you want to publish')
                                    ->columns(2),
                                Forms\Components\Select::make('status')
                                    ->options(SocialMediaPost::getStatuses())
                                    ->required()
                                    ->disabled(fn ($record) => 
                                        $record && $record->status === SocialMediaPost::STATUS_PUBLISHED),
                            ])
                            ->columnSpan(2),
                        
                        Section::make('Media & Links')
                            ->schema([
                                Forms\Components\TextInput::make('link')
                                    ->url()
                                    ->label('Link (optional)')
                                    ->helperText('Add a URL to your post'),
                                Forms\Components\FileUpload::make('image')
                                    ->image()
                                    ->label('Image (optional)')
                                    ->disk('public')
                                    ->directory('social-media-posts')
                                    ->imagePreviewHeight('250')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                                    ->helperText('Upload images (max 5MB, JPG/PNG/GIF)'),
                            ])
                            ->columnSpan(2),

                        Section::make('Preview')
                            ->schema([
                                Forms\Components\View::make('filament.components.social-media-preview')
                                    ->visible(fn ($get) => !empty($get('content')))
                            ])
                            ->columnSpan(2),

                        Section::make('Analytics')
                            ->schema([
                                Forms\Components\Placeholder::make('Analytics')
                                    ->content(function (SocialMediaPost $record) {
                                        return view('filament.components.social-media-analytics', [
                                            'post' => $record,
                                            'detailed' => true
                                        ]);
                                    }),
                                Forms\Components\View::make('filament.components.engagement-chart')
                                    ->visible(fn ($record) => 
                                        $record && $record->status === SocialMediaPost::STATUS_PUBLISHED),
                            ])
                            ->columnSpan(1)
                            ->hidden(fn ($record) => 
                                $record === null || $record->status !== SocialMediaPost::STATUS_PUBLISHED),
                    ]),
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
