<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages\ListArticles;
use App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages\ViewArticle;
use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KnowledgeBaseArticleResource extends Resource
{
    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Knowledge base';

    protected static ?string $slug = 'articles';

    /**
     * A customer sees only the published articles of their own tenant. Filament
     * resolves table rows and single-record routes through this query, so an
     * unpublished or other-team article id 404s.
     */
    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('team_id', self::currentTeamId())
            ->where('is_published', true);
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        // Rendered read-only by the View page.
        return $schema->components([
            TextInput::make('title')->disabled(),
            TextInput::make('category')->disabled(),
            Textarea::make('content')->disabled()->rows(12)->columnSpanFull(),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => KnowledgeBaseArticle::query()
                        ->where('team_id', self::currentTeamId())
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('title');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'view' => ViewArticle::route('/{record}'),
        ];
    }

    private static function currentTeamId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? $user->getAttribute('current_team_id') : null;
    }
}
