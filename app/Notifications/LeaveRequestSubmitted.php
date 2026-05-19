<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected Leave $leave)
    {
        $this->leave->loadMissing('user');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New leave request',
            'body' => $this->leave->user->name.' requested leave from '.$this->formattedDate($this->leave->start_date).' to '.$this->formattedDate($this->leave->end_date).'.',
            'action_url' => route('admin.leave-requests'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($message['title'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line($message['body'])
            ->action('Review leave request', $message['action_url']);
    }

    private function formattedDate(string $date): string
    {
        return \Carbon\Carbon::parse($date)->format('d/m/Y');
    }
}
