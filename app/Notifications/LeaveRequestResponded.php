<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Leave $leave)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        $status = strtolower($this->leave->status);

        return [
            'title' => 'Leave request '.ucfirst($status),
            'body' => 'Your leave request from '.$this->formattedDate($this->leave->start_date).' to '.$this->formattedDate($this->leave->end_date).' was '.$status.'.',
            'action_url' => route('leave.view'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($message['title'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line($message['body'])
            ->action('View your leave requests', $message['action_url']);
    }

    private function formattedDate(string $date): string
    {
        return \Carbon\Carbon::parse($date)->format('d/m/Y');
    }
}
