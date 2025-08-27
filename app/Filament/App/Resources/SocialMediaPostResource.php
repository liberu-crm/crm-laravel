<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\View;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\SocialMediaPostResource\Pages\ListSocialMediaPosts;
use App\Filament\App\Resources\SocialMediaPostResource\Pages\CreateSocialMediaPost;
use App\Filament\App\Resources\SocialMediaPostResource\Pages\EditSocialMediaPost;
use Exception;
use App\Filament\App\Resources\SocialMediaPostResource\Pages;
use App\Models\SocialMediaPost;
use App\Services\FacebookAdsService;
use App\Services\LinkedInAdsService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Tabs;

class SocialMediaPostResource extends Resource
{
    protected static ?string $model = SocialMediaPost::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-share';
    protected static string | \UnitEnum | null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Post Content')
                            ->schema([
                                Textarea::make('content')
                                    ->required()
                                    ->maxLength(65535)
                                    ->helperText('Write your post content here. Character limits vary by platform.')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => 
                                        $set('character_count', strlen($state))),
                                TextInput::make('character_count')
                                    ->label('Character Count')
                                    ->disabled()
                                    ->dehydrated(false),
                                DateTimePicker::make('scheduled_at')
                                    ->required()
                                    ->helperText('Schedule when this post should be published')
                                    ->minDate(now())
                                    ->withoutSeconds(),
                                MultiSelect::make('platforms')
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
                                Select::make('status')
                                    ->options(SocialMediaPost::getStatuses())
                                    ->required()
                                    ->disabled(fn ($record) => 
                                        $record && $record->status === SocialMediaPost::STATUS_PUBLISHED),
                            ])
                            ->columnSpan(2),
                        
                        Section::make('Media & Links')
                            ->schema([
                                TextInput::make('link')
                                    ->url()
                                    ->label('Link (optional)')
                                    ->helperText('Add a URL to your post'),
                                FileUpload::make('image')
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
                                View::make('filament.components.social-media-preview')
                                    ->visible(fn ($get) => !empty($get('content')))
                            ])
                            ->columnSpan(2),

                        Section::make('Analytics')
                            ->schema([
                                Placeholder::make('Analytics')
                                    ->content(function (SocialMediaPost $record) {
                                        return view('filament.components.social-media-analytics', [
                                            'post' => $record,
                                            'detailed' => true
                                        ]);
                                    }),
                                View::make('filament.components.engagement-chart')
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
                TextColumn::make('content')->limit(50),
                TagsColumn::make('platforms'),
                TextColumn::make('scheduled_at')
                    ->dateTime(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'draft',
                        'warning' => 'scheduled',
                        'success' => 'published',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('likes')
                    ->sortable(),
                TextColumn::make('shares')
                    ->sortable(),
                TextColumn::make('comments')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SocialMediaPost::getStatuses()),
                SelectFilter::make('platforms')
                    ->options([
                        'facebook' => 'Facebook',
                        'linkedin' => 'LinkedIn',
                        'twitter' => 'Twitter/X',
                        'instagram' => 'Instagram',
                        'youtube' => 'YouTube',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('publish')
                    ->action(function (SocialMediaPost $record) {
                        self::publishPost($record);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (SocialMediaPost $record) => $record->status === SocialMediaPost::STATUS_SCHEDULED),
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
            'index' => ListSocialMediaPosts::route('/'),
            'create' => CreateSocialMediaPost::route('/create'),
            'edit' => EditSocialMediaPost::route('/{record}/edit'),
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
            } catch (Exception $e) {
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
