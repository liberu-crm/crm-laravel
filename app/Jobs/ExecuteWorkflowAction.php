<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflowAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $action;
    protected $lead;

    public function __construct(array $action, Lead $lead)
    {
        $this->action = $action;
        $this->lead = $lead;
    }

    public function handle()
    {
        switch ($this->action['type']) {
            case 'send_email':
                $this->sendEmail();
                break;
            case 'update_contact':
                $this->updateContact();
                break;
            // Add more action types as needed
        }
    }

    protected function sendEmail()
    {
        // Implement email sending logic
        // You can use Laravel's built-in mail functionality or a third-party service
    }

    protected function updateContact()
    {
        // Implement contact update logic
        $this->lead->contact->update($this->action['data']);
    }
}