<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_open_another_users_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $leave = Leave::create([
            'user_id' => $employee->id,
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $admin->notify(new LeaveRequestSubmitted($leave));
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($otherAdmin)
            ->patch(route('notifications.open', $notification))
            ->assertForbidden();

        $this->assertNull($notification->refresh()->read_at);
    }
}
