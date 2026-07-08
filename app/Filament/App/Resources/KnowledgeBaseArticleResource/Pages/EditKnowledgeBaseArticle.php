<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\App\Resources\KnowledgeBaseArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeBaseArticle extends EditRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
