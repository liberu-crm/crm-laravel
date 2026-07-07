<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
