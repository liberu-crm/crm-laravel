<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Team;
use App\Models\User;

class TeamInvitation extends Notification
{
    use Queueable;

    protected $team;
    protected $invitedBy;

    /**
     * Create a new notification instance.

     *
     * @return void
     */
    public function __construct(Team $team, User $invitedBy)
    {
        $this->team = $team;
        $this->invitedBy = $invitedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('You have been invited to join the team ' . $this->team->name . ' by ' . $this->invitedBy->name . '.')
                    ->action('Accept Invitation', url('/team-invitations/' . $notifiable->id . '/accept'))
                    ->line('If you did not expect to receive an invitation to this team, you may discard this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}