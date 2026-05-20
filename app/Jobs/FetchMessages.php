<?php

namespace App\Jobs;

use App\Actions\Helpdesk\CreateTicketFromEmail;
use App\Actions\Helpdesk\CreateTicketFromWhatsApp;
use App\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        MessageService $messageService,
        CreateTicketFromEmail $createTicketFromEmail,
        CreateTicketFromWhatsApp $createTicketFromWhatsApp
    ) {
        $messages = $messageService->getUnreadMessages();

        foreach ($messages['email'] as $message) {
            $fullMessage = $messageService->getMessage($message->getId(), 'email');
            $createTicketFromEmail->execute($fullMessage);
        }

        foreach ($messages['whatsapp'] as $message) {
            $fullMessage = $messageService->getMessage($message['id'], 'whatsapp');
            $createTicketFromWhatsApp->execute($fullMessage);
        }
    }
}