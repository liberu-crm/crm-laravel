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

    public function __construct(protected array $action, protected \App\Models\Lead $lead)
    {
    }

    public function handle(): void
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
        $allowed = ['name', 'last_name', 'email', 'phone_number', 'status', 'industry', 'company_size', 'annual_revenue', 'lifecycle_stage', 'custom_fields'];
        $data = array_intersect_key($this->action['data'] ?? [], array_flip($allowed));
        $this->lead->contact->update($data);
    }
}
