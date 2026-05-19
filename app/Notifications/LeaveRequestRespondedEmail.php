<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;

class LeaveRequestRespondedEmail extends LeaveRequestResponded implements ShouldQueue
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
