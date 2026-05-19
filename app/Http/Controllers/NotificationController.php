<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== $request->user()->id || $notification->notifiable_type !== $request->user()::class) {
            abort(403, 'Unauthorised action.');
        }

        $notification->markAsRead();

        return redirect($notification->data['action_url'] ?? route('dashboard'));
    }
}
