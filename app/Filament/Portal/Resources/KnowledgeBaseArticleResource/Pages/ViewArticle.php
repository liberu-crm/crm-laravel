<?php

declare(strict_types=1);

namespace App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewArticle extends ViewRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected function getHeaderActions(): array
    {
        // The record is already scoped to the customer's team + published, so
        // feedback can only land on an article they may actually read.
        return [
            Action::make('helpful')
                ->label('Helpful')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->action(fn () => $this->recordFeedback('helpful_count', 'helpful')),
            Action::make('not_helpful')
                ->label('Not helpful')
                ->icon('heroicon-o-hand-thumb-down')
                ->color('gray')
                ->action(fn () => $this->recordFeedback('not_helpful_count', 'not_helpful')),
        ];
    }

    private function recordFeedback(string $column, string $vote): void
    {
        $articleId = $this->getRecord()->getKey();
        $userId = Auth::id();

        // One vote per customer per article — a second click doesn't re-count.
        $alreadyVoted = DB::table('kb_article_votes')
            ->where('knowledge_base_article_id', $articleId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyVoted) {
            Notification::make()->title('You have already voted on this article')->warning()->send();

            return;
        }

        DB::table('kb_article_votes')->insert([
            'knowledge_base_article_id' => $articleId,
            'user_id' => $userId,
            'vote' => $vote,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getRecord()->increment($column);

        Notification::make()->title('Thanks for your feedback')->success()->send();
    }
}
