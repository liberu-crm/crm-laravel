<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\App\Resources\KnowledgeBaseArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBaseArticle extends CreateRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;
}
