<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send reminders for tasks';

    public function handle(ReminderService $reminderService): void
    {
        $this->info('Sending reminders...');
        $reminderService->sendReminders();
        $this->info('Reminders sent successfully.');
    }
}
