<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\App\Resources\KnowledgeBaseArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
