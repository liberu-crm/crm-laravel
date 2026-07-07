<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        // The record is already scoped to the customer's team + published, so
        // feedback can only land on an article they may actually read.
        // ponytail: votes aren't deduped per customer (no per-user vote table) —
        // a later slice if abuse matters.
        return [
            Action::make('helpful')
                ->label('Helpful')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->action(fn () => $this->recordFeedback('helpful_count')),
            Action::make('not_helpful')
                ->label('Not helpful')
                ->icon('heroicon-o-hand-thumb-down')
                ->color('gray')
                ->action(fn () => $this->recordFeedback('not_helpful_count')),
        ];
    }

    private function recordFeedback(string $column): void
    {
        $this->getRecord()->increment($column);

        Notification::make()->title('Thanks for your feedback')->success()->send();
    }
}
