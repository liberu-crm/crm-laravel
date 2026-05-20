<?php

namespace App\Filament\App\Resources\Components;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

class TimelineView extends Column
{
    protected string $view = 'filament.resources.components.timeline-view';

    public function getState(): array
    {
        $record = $this->getRecord();

        return $this->getTimelineItems($record);
    }

    protected function getTimelineItems(Model $record): array
    {
        $items = [];

        // Add notes
        foreach ($record->notes as $note) {
            $items[] = [
                'type' => 'note',
                'content' => $note->content,
                'date' => $note->created_at,
            ];
        }

        // Add activities
        foreach ($record->activities as $activity) {
            $items[] = [
                'type' => 'activity',
                'content' => $activity->description,
                'date' => $activity->created_at,
            ];
        }

        // Add deals (for contacts and companies)
        if (method_exists($record, 'deals')) {
            foreach ($record->deals as $deal) {
                $items[] = [
                    'type' => 'deal',
                    'content' => "Deal created: {$deal->name}",
                    'date' => $deal->created_at,
                ];
            }
        }

        // Sort items by date
        usort($items, function ($a, $b) {
            return $b['date']->getTimestamp() - $a['date']->getTimestamp();
        });

        return $items;
    }
}
