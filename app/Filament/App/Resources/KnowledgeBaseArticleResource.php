<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\CreateKnowledgeBaseArticle;
use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\EditKnowledgeBaseArticle;
use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\ListKnowledgeBaseArticles;
use App\Filament\Concerns\EnforcesResourcePermissions;
use App\Models\KnowledgeBaseArticle;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Staff-facing authoring for the customer portal knowledge base (#483 is the
 * read-only portal browse). On the team-scoped app panel, KnowledgeBaseArticle
 * (IsTenantModel) auto-filters reads to the current team and auto-stamps team_id
 * on create — so articles a team authors here are exactly what its customers
 * browse in the portal (which scopes team_id + is_published).
 */
class KnowledgeBaseArticleResource extends Resource
{
    use EnforcesResourcePermissions;

    protected static ?string $model = KnowledgeBaseArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Help Desk';

    protected static ?string $navigationLabel = 'Knowledge base';

    protected static ?string $slug = 'knowledge-base';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('category')->maxLength(255),
            Textarea::make('content')->required()->rows(12)->columnSpanFull(),
            Toggle::make('is_published')
                ->default(true)
                ->helperText('Unpublished articles are hidden from the customer portal.'),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('category')->badge()->sortable(),
                IconColumn::make('is_published')->boolean()->label('Published'),
                TextColumn::make('helpful_count')->label('Helpful')->sortable(),
                TextColumn::make('not_helpful_count')->label('Not helpful')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => KnowledgeBaseArticle::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBaseArticles::route('/'),
            'create' => CreateKnowledgeBaseArticle::route('/create'),
            'edit' => EditKnowledgeBaseArticle::route('/{record}/edit'),
        ];
    }
}
