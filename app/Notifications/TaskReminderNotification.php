<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $type;

    public function __construct(Task $task, string $type)
    {
        $this->task = $task;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->line('Reminder: You have a task due soon.')
            ->line('Task: ' . $this->task->name)
            ->line('Due Date: ' . $this->task->due_date->format('Y-m-d H:i'));

        switch ($this->type) {
            case 'contact':
                $message->line('Related Contact: ' . $this->task->contact->name);
                break;
            case 'lead':
                $message->line('Related Lead: ' . $this->task->lead->name);
                break;
            case 'assigned':
                $message->line('This task is assigned to you.');
                break;
        }

        return $message
            ->action('View Task', url('/tasks/' . $this->task->id))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'due_date' => $this->task->due_date->toDateTimeString(),
            'type' => $this->type,
        ];
    }
}