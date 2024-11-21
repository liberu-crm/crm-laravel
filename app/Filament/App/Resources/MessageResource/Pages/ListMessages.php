

<?php

namespace App\Filament\App\Resources\MessageResource\Pages;

use App\Filament\App\Resources\MessageResource;
use App\Services\UnifiedHelpDeskService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->action(function (UnifiedHelpDeskService $helpDeskService) {
                    try {
                        $messages = $helpDeskService->getAllMessages(null, false);
                        
                        foreach ($messages as $message) {
                            static::getModel()::updateOrCreate(
                                [
                                    'id' => $message['id'],
                                    'channel' => $message['channel']
                                ],
                                [
                                    'sender' => $message['from'],
                                    'content' => $message['content'],
                                    'timestamp' => $message['timestamp'],
                                    'priority' => $message['priority'],
                                    'status' => 'unread',
                                    'account_id' => $message['account_id'],
                                    'thread_id' => $message['thread_id'],
                                    'metadata' => $message['metadata'],
                                ]
                            );
                        }

                        Cache::tags(['messages'])->flush();

                        Notification::make()
                            ->title('Messages synced successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error syncing messages')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->icon('heroicon-o-arrow-path')
                ->label('Sync Messages'),
        ];
    }
}