<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued mail-only variant so email delivery cannot delay the admin response flow.
 */
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
